<?php
/**
 * Sample sub-plugin for NP_List
 * 
 * コメント付きの簡単な作例です。サブプラグイン作成の参考にどうぞ。
 */
class NP_ListOfSample extends NP_ListOfSubPlugin 
{
    /**
     * サブプラグインのデフォルトテンプレートを定義します
     * 
     * 定義は必須ではないですが、定義されていればこれをベースにして
     * Nucleusのテンプレートや、NP_Counterによるカスタムテンプレートのデータを
     * 上書きすることができます。
     * 
     * @access private
     * @param  void
     * @return array
     */
    function _getDefaultTemplate() 
    {
        $template['BODY']   = '<dd<%class%>><a href="<%bloglink%>"><%date%><%blogname%>[<%amount%>]</a></dd>' ."\n";
        
        return $template;
    }
    
    /**
     * パラメータのデフォルト値を定義します
     * 
     * 定義は必須ではないですが、定義されていれば明示されていないパラメータのデフォルト値を
     * サブプラグイン独自のデフォルト値にできます。
     * 
     * @access private
     * @param  void
     * @return void
     */
    function _setDefaultParams() 
    {
        if (!isset($this->params['amount'])) $this->params['amount'] = 3;
        if (!isset($this->params['length'])) $this->params['length'] = 10;
        if (!isset($this->params['trimmarker'])) $this->params['trimmarker'] = '+';
    }
    
    /**
     * リストを出力します
     * 
     * サブプラグインがinit(), reset()により初期化された後に呼ばれます。
     * 
     * @access public
     * @param  void
     * @return void
     */
    function main()
    {
        global $manager, $blogid;
        
        //パラメータは $this->params で参照できます。
        //注：パラメータ"len" or "length"は、$this->param["length"]に格納されています。
        $p =& $this->params;
        
        //blogオブジェクトを取得しています。
        $b =& $manager->getBlog($blogid);
        
        //テンプレートは $this->_getTemplate() を呼んで取得します。
        //実際は初期化時にパラメータから自動読込してるので、ここでは参照を取得するだけです。
        //Nucleus標準テンプレ、NP_Containerによるカスタムテンプレに対応してます。
        $template =& $this->_getTemplate();
        
        //フラグは $this->flg で参照できます。
        $flg =& $this->flg;
        
        //DBよりデータを取得する準備をします。
        $query = 'SELECT bnumber AS blogid, bname AS blogname, bshortname, bdesc AS blogdesc, burl'
            .' FROM '.sql_table('blog');
        
        //フィルターをSQL文に変換します。
        if ($p['filter']) {
            
            //WHERE節ではカラム名のエイリアス（別名）が使えないので、変換マップ $aliasmap を用意します。
            $aliasmap['search'] = array('blogid');
            $aliasmap['replace'] = array('bnumber');
            
            //カレント値(@)の変換は、blogid, catid, memberid, arcdate についてはデフォルトで対応します。
            //必要があれば $this->_getFilter() の第3パラメータにカレント値の連想配列を
            //渡すこともできます。
            //例：
            //$current = array('blogid' => $blogid, 'catid' => $catid, 'hoge' => $hoge);
            //$fstr = $this->_getFilter($p['filter'], $aliasmap, $current);
            
            //フィルターをWHERE節用の文字列に変換
            $fstr = $this->_getFilter($p['filter'], $aliasmap);
            if ($fstr) $query .= ' WHERE ' . $fstr;
        }
        $order = ($p['order']) ? $p['order'] : 'blogid ASC';
        $query .= ' ORDER BY '. $order;
        $query .= ' LIMIT 0, ' . $p['amount'];
        
        //SQLを発行してデータを取得します。
        $res = sql_query($query);
        
        //結果が得られたら、リスト表示のループを開始します。
        if (mysql_num_rows($res)) {
            
            //テンプレート変数をスキャンしてフラグをセットします。while内の条件分岐に使います。
            $flg['class']   = $this->_scan($template['BODY'], '<%class%>');
            $flg['amount']  = $this->_scan($template['BODY'], '<%amount%>');
            
            $stripe = false;
            $nowdate = mysqldate($b->getCorrectTime());
            while ($data = mysql_fetch_assoc($res)) {
                //データを1行ずつ $data という連想配列で受けます。
                
                //元のデータを加工します。
                $data['blogname'] = shorten($data['blogname'], $p['length'], $p['trimmarker']);
                
                //DB上に無いデータを追加していきます。
                $data['bloglink'] = createBlogidLink($data['blogid']);
                
                //クラス属性をセットします。
                if ($flg['class']) {
                    $classes = array(
                        'current' => ($blogid == $data['blogid']),
                        'stripe'  => ($flg['stripe'] and $stripe),
                        );
                    $data['class'] = $this->_makeClassProperty($classes);
                }
                //アイテム数をセットします。
                if ($flg['amount']) {
                    $data['amount'] = quickQuery('SELECT COUNT(*) AS result FROM '. sql_table('item')
                        .' WHERE iblog='. $data['blogid'] 
                        .' AND itime<='. $nowdate .' AND idraft=0');
                }
                
                //テンプレートに対応する値（$data）を流し込み、リストを1行出力します。
                echo $this->_fill($template['BODY'], $data);
                $stripe = ($stripe) ? false : true; //toggle
            }
            mysql_free_result($res);
        }
    }
}
?>
