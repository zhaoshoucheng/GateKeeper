(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-fa07cfb0"],{"41d7":function(e,i,l){},ee93:function(e,i,l){"use strict";l("41d7")},fb94:function(e,i,l){"use strict";l.r(i);var a=function(){var e=this,i=e.$createElement,l=e._self._c||i;return l("div",{staticClass:"mixin-components-container"},[l("div",[e._v(e._s(e.title))]),e._l(e.card_config,(function(i){return l("CardPanel",{key:i.unique_name,staticClass:"card",attrs:{config:i},on:{error:e.errCard},model:{value:i.valued,callback:function(l){e.$set(i,"valued",l)},expression:"item.valued"}})})),e._l(e.auth_config_selected,(function(i){return l("CardPanel",{key:i.unique_name,staticClass:"card",attrs:{config:i},on:{error:e.errCard},model:{value:i.valued,callback:function(l){e.$set(i,"valued",l)},expression:"item.valued"}})})),e._l(e.auth_config_options,(function(i){return l("CardPanel",{key:i.unique_name,staticClass:"card",attrs:{config:i},on:{error:e.errCard},model:{value:i.valued,callback:function(l){e.$set(i,"valued",l)},expression:"item.valued"}})})),e._l(e.load_balance_config_selected,(function(i){return l("CardPanel",{key:i.unique_name,staticClass:"card",attrs:{config:i},on:{error:e.errCard},model:{value:i.valued,callback:function(l){e.$set(i,"valued",l)},expression:"item.valued"}})})),e._l(e.load_balance_config_options,(function(i){return l("CardPanel",{key:i.unique_name,staticClass:"card",attrs:{config:i},on:{error:e.errCard},model:{value:i.valued,callback:function(l){e.$set(i,"valued",l)},expression:"item.valued"}})})),l("div",{staticClass:"bottom"},[l("el-button",{attrs:{round:""},on:{click:e.clickCancel}},[e._v("取消")]),l("el-button",{attrs:{type:"primary",round:""},on:{click:e.clickOk}},[e._v("确认")])],1)],2)},t=[],n=(l("ac67"),l("e680"),l("a7e5"),l("8ee4")),d=l("c1d5"),_=(l("1bc7"),l("32ea"),l("e186"),l("7590")),r=l("22ce"),f=l("7ab0");function u(e,i){var l=Object.keys(e);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(e);i&&(a=a.filter((function(i){return Object.getOwnPropertyDescriptor(e,i).enumerable}))),l.push.apply(l,a)}return l}function s(e){for(var i=1;i<arguments.length;i++){var l=null!=arguments[i]?arguments[i]:{};i%2?u(Object(l),!0).forEach((function(i){Object(n["a"])(e,i,l[i])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(l)):u(Object(l)).forEach((function(i){Object.defineProperty(e,i,Object.getOwnPropertyDescriptor(l,i))}))}return e}var o={http:"HTTP服务",tcp:"TCP服务",grpc:"GRPC服务"},c={name:"ServiceCreateHttp",components:{CardPanel:f["a"]},data:function(){return{title:"",card_config:[{display_name:"基本信息",sort:0,postion:"normal",unique_name:"base_info",items:[{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"6-128位字母数字下划线",field_option:"",field_value:"",field_default_value:"",field_unique_name:"service_name",field_display_name:"服务名称",field_required:!0,field_valid_rule:"",file_description:"基础的服务名称"},{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"最多255字符，必填",field_option:"",field_value:"",field_default_value:"",field_unique_name:"service_description",field_display_name:"服务描述",field_required:!0,field_valid_rule:"",file_description:"基础的服务描述"},{field_type:"textarea",field_display:"inline",field_clear:"none",field_placeholder:"例如www.baidu.com",field_option:"",field_value:"",field_default_value:"",field_unique_name:"service_hosts",field_display_name:"服务域名",field_required:!0,field_valid_rule:"",file_description:"基础的服务域名"},{field_type:"textarea",field_display:"inline",field_clear:"none",field_placeholder:"例如/path",field_option:"",field_value:"",field_default_value:"",field_unique_name:"service_http_paths",field_display_name:"服务地址",field_required:!0,field_valid_rule:"",file_description:"基础的服务地址"},{field_type:"switch",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"0|1,关闭|开启",field_value:"",field_default_value:"1",field_unique_name:"need_strip_url",field_display_name:"是否开启strip_url",field_required:!0,field_valid_rule:""},{field_type:"radio",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"round|weight_round|consistent_hash|random,轮询|权重轮询|一致性hash|随机",field_value:"consistent_hash",field_default_value:"",field_unique_name:"transfer_loadbalance_strategy",field_display_name:"负载类型",field_required:!0,field_valid_rule:""},{field_type:"textarea",field_display:"",field_clear:"",field_placeholder:"每个规则一行 如：协议://127.0.0.1:8701 100 ",field_option:"",field_value:"",field_default_value:"",field_unique_name:"upstream_list",field_display_name:"下游服务器ip+权重",field_required:!0,field_valid_rule:"",file_description:""}]}],auth:[],auth_config_selected:"",load_balance:[],load_balance_config_selected:"",isEdit:!1,submitButDisabled:!1,form:{service_name:"",service_desc:"",rule_type:0,rule:"",need_https:0,need_websocket:0,need_strip_uri:1,url_rewrite:"",header_transfor:"",round_type:2,ip_list:"",disf_name:"",disf_cluster_name:"",weight_list:"",upstream_connect_timeout:"",upstream_header_timeout:"",upstream_idle_timeout:"",upstream_max_idle:"",open_auth:0,black_list:"",white_list:"",clientip_flow_limit:"",service_flow_limit:""}}},computed:{auth_config_options:{get:function(){var e=this;return console.log("info",this.auth,this.auth_config_selected),this.auth&&this.auth.filter((function(i){return e.auth_config_selected[0].items[0].child[0].field_value===i.unique_name}))}},load_balance_config_options:{get:function(){var e=this;return this.load_balance&&this.load_balance.filter((function(i){return e.load_balance_config_selected[0].items[0].child[0].field_value===i.unique_name}))}}},created:function(){var e=Object(_["a"])(regeneratorRuntime.mark((function e(){var i,l,a;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:if(i=this.$route.query&&this.$route.query.type,this.type=i,l=this.$route.query&&this.$route.query.id,i&&(this.title="创建"+o[i]),!(l>0)){e.next=10;break}this.isEdit=!0,this.fetchData(l),this.title="修改"+o[i],e.next=16;break;case 10:if(!(Object.keys(o).filter((function(e){return i===e})).length>0)){e.next=16;break}return e.next=13,Object(r["h"])();case 13:a=e.sent,"http"!==this.type&&(this.card_config=[{display_name:"基本信息",sort:0,postion:"normal",unique_name:"base_info",items:[{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"6-128位字母数字下划线",field_option:"",field_value:"",field_default_value:"",field_unique_name:"service_name",field_display_name:"服务名称",field_required:!0,field_valid_rule:"",file_description:"".concat(this.type,"的服务名称")},{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"最多255字符，必填",field_option:"",field_value:"",field_default_value:"",field_unique_name:"service_description",field_display_name:"服务描述",field_required:!0,field_valid_rule:"",file_description:"".concat(this.type,"的服务描述")},{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"本地监听的端口",field_option:"",field_value:"",field_default_value:"",field_unique_name:"service_port",field_display_name:"服务端口",field_required:!0,field_valid_rule:"",file_description:"本地监听可访问的端口"},{field_type:"radio",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"round|weight_round|consistent_hash|random,轮询|权重轮询|一致性hash|随机",field_value:"",field_default_value:"",field_unique_name:"transfer_loadbalance_strategy",field_display_name:"负载类型",field_required:!0,field_valid_rule:"",file_description:"".concat(this.type,"的loadbalance策略")},{field_type:"textarea",field_display:"",field_clear:"",field_placeholder:"每个规则一行 如：".concat(this.type,"://127.0.0.1:8701 100"),field_option:"",field_value:"",field_default_value:"",field_unique_name:"upstream_list",field_display_name:"下游服务器ip+权重",field_required:!0,field_valid_rule:"",file_description:"".concat(this.type,"的下游服务器ip+权重")}]}]),this.card_config=this.formatChange([].concat(Object(d["a"])(this.card_config),Object(d["a"])(a.data[i])));case 16:case"end":return e.stop()}}),e,this)})));function i(){return e.apply(this,arguments)}return i}(),methods:{decodeItems:function(e){var i=[];return e&&e.forEach((function(e){e.child.forEach((function(e){i.push(e)}))})),i},encodeItems:function(e){var i=[],l=[],a=!1;return e.forEach((function(e){e.field_clear||(e.field_clear="none"),a?l.push(e):"none"===e.field_clear&&i.push(s(s({},e),{},{child:[e]})),a||"left"!==e.field_clear||(l=[],l.push(e),a=!0),a&&"right"===e.field_clear&&(i.push(s(s({},l[0]),{},{child:l})),a=!1)})),i},sendFormInfo:function(e){var i=this,l=JSON.parse(JSON.stringify(e));return l.forEach((function(e){e.items=i.decodeItems(e.items)})),l},formatChange:function(e,i){var l=this,a=[],t=[{display_name:"默认验证",items:[],postion:"auth",sort:2,unique_name:"default_auth"}],n=[{display_name:"默认负载",items:[],postion:"loadbalance",sort:2,unique_name:"default_loadbalance"}],d="",_="",r="",f="";e.forEach((function(e){"normal"===e.postion?(e.items=e.items&&l.encodeItems(e.items),e.items&&0!==e.items.length&&a.push(e)):"auth"===e.postion?(e.items=e.items&&l.encodeItems(e.items),d+=e.unique_name+"|",_+=e.display_name+"|",t.push(e)):"loadbalance"===e.postion&&(e.items=e.items&&l.encodeItems(e.items),r+=e.unique_name+"|",f+=e.display_name+"|",n.push(e))})),d+="default_auth",_+="默认验证",r+="default_loadbalance",f+="默认负载";var u={field_type:"select",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:d+","+_,field_value:i&&i.data.auth_type&&-1!==t.findIndex((function(e){return e.unique_name===i.data.auth_type}))?i.data.auth_type:"默认验证",field_default_value:"",field_unique_name:"transfer_auth_type",field_display_name:"验证规则",field_required:!1,field_valid_rule:""},o={field_type:"select",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:r+","+f,field_value:i&&i.data.load_balance_type&&-1!==n.findIndex((function(e){return e.unique_name===i.data.load_balance_type}))?i.data.load_balance_type:"默认负载",field_default_value:"",field_unique_name:"transfer_loadbalance_type",field_display_name:"负载策略",field_required:!1,field_valid_rule:""};this.auth_config_selected=[{display_name:"服务验证",sort:3,postion:"normal",unique_name:"auth_type",items:[s({child:[u]},u)]}],this.auth=[].concat(t),this.load_balance_config_selected=[{display_name:"服务负载",sort:3,postion:"normal",unique_name:"load_balance_type",items:[s({child:[o]},o)]}],this.load_balance=[].concat(n);var c=[].concat(a);return c},clickCancel:function(e){this.$router.go(-1)},clickOk:function(e){var i=this;if(this.err)this.$message.error({message:this.err}),this.err="";else{var l=this.sendFormInfo(this.card_config),a=l.shift(),t=this.sendFormInfo(this.auth_config_options),n=[];this.auth.forEach((function(e){var i;t.length&&e.unique_name===t[0].unique_name&&(i=n).push.apply(i,Object(d["a"])(t))})),n=this.sendFormInfo(n);var _=this.sendFormInfo(this.load_balance_config_options),f=[];this.load_balance.forEach((function(e){var i;_.length&&e.unique_name===_[0].unique_name&&(_[0].items&&_[0].items.length>0&&(_[0].items[0].child=JSON.parse(JSON.stringify(_[0].items))),(i=f).push.apply(i,Object(d["a"])(_)))})),f=this.sendFormInfo(f);var u={http:0,tcp:1,grpc:2},o="";o="http"!==this.type?{plugin_conf:JSON.stringify(this.pluginConfChange([].concat(Object(d["a"])(l),Object(d["a"])(n),Object(d["a"])(f)))),load_type:u[this.type],service_name:a.items[0].field_value,service_desc:a.items[1].field_value,port:Number(a.items[2].field_value),load_balance_strategy:a.items[3].field_value,load_balance_type:f[0].unique_name,auth_type:n[0]?n[0].unique_name:"",upstream_list:a.items[4].field_value}:{plugin_conf:JSON.stringify(this.pluginConfChange([].concat(Object(d["a"])(l),Object(d["a"])(n),Object(d["a"])(f)))),load_type:u[this.type],service_name:a.items[0].field_value,service_desc:a.items[1].field_value,http_hosts:a.items[2].field_value,http_paths:a.items[3].field_value,need_strip_uri:Number(""+a.items[4].field_value?a.items[4].field_value:a.items[4].field_default_value),load_balance_strategy:a.items[5].field_value,load_balance_type:f[0].unique_name,auth_type:n[0]?n[0].unique_name:"",upstream_list:a.items[6].field_value};var c=this.$route.query&&this.$route.query.id;c>0?Object(r["j"])(s(s({},o),{},{id:+c})).then((function(){i.$message.success({message:o.service_name+"服务修改成功"}),i.$router.go(-1)})):Object(r["a"])(o).then((function(){i.$message.success({message:o.service_name+"服务创建成功"}),i.$router.go(-1)}))}},pluginConfChange:function(e){var i={};return e.forEach((function(e){i[e.unique_name]={},e.items.forEach((function(l){i[e.unique_name][l.field_unique_name]=l.field_value}))})),i},errCard:function(e){this.err=e},fetchData:function(){var e=Object(_["a"])(regeneratorRuntime.mark((function e(i){var l,a,t;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return l={id:i},e.next=3,Object(r["f"])(l);case 3:a=e.sent,t=[{display_name:"基本信息",sort:0,postion:"normal",unique_name:"base_info",items:[{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"6-128位字母数字下划线",field_option:"",field_value:a.data.service_name,field_default_value:"",field_unique_name:"service_name",field_display_name:"服务名称",field_required:!0,field_valid_rule:"",file_description:"基础的服务名称"},{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"最多255字符，必填",field_option:"",field_value:a.data.service_desc,field_default_value:"",field_unique_name:"service_description",field_display_name:"服务描述",field_required:!0,field_valid_rule:"",file_description:"基础的服务描述"},{field_type:"textarea",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"",field_value:a.data.http_hosts,field_default_value:"",field_unique_name:"service_hosts",field_display_name:"服务域名",field_required:!0,field_valid_rule:"",file_description:"基础的服务域名"},{field_type:"textarea",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"",field_value:a.data.http_paths,field_default_value:"",field_unique_name:"service_http_paths",field_display_name:"服务地址",field_required:!0,field_valid_rule:"",file_description:"基础的服务地址"},{field_type:"switch",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"0|1,关闭|开启",field_value:a.data.need_strip_uri,field_default_value:"",field_unique_name:"need_strip_url",field_display_name:"是否开启strip_url",field_required:!0,field_valid_rule:""},{field_type:"radio",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"round|weight_round|consistent_hash|random,轮询|权重轮询|一致性hash|随机",field_value:a.data.load_balance_strategy,field_default_value:"",field_unique_name:"transfer_loadbalance_strategy",field_display_name:"负载类型",field_required:!0,field_valid_rule:""},{field_type:"textarea",field_display:"",field_clear:"",field_placeholder:"每个规则一行 如：grpc://127.0.0.1:8701 100 ",field_option:"",field_value:a.data.upstream_list,field_default_value:"",field_unique_name:"upstream_list",field_display_name:"下游服务器ip+权重",field_required:!0,field_valid_rule:"",file_description:""}]}],"http"!==this.type&&(t=[{display_name:"基本信息",sort:0,postion:"normal",unique_name:"base_info",items:[{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"6-128位字母数字下划线",field_option:"",field_value:a.data.service_name,field_default_value:"",field_unique_name:"service_name",field_display_name:"服务名称",field_required:!0,field_valid_rule:"",file_description:"".concat(this.type,"的服务名称")},{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"最多255字符，必填",field_option:"",field_value:a.data.service_desc,field_default_value:"",field_unique_name:"service_description",field_display_name:"服务描述",field_required:!0,field_valid_rule:"",file_description:"tcp的服务描述"},{field_type:"input",field_display:"inline",field_clear:"none",field_placeholder:"本地监听的端口",field_option:"",field_value:a.data.port,field_default_value:"",field_unique_name:"service_port",field_display_name:"服务端口",field_required:!0,field_valid_rule:"",file_description:"本地监听可访问的端口"},{field_type:"radio",field_display:"inline",field_clear:"none",field_placeholder:"",field_option:"round|weight_round|consistent_hash|random,轮询|权重轮询|一致性hash|随机",field_value:a.data.load_balance_strategy,field_default_value:"",field_unique_name:"transfer_loadbalance_strategy",field_display_name:"负载类型",field_required:!0,field_valid_rule:""},{field_type:"textarea",field_display:"",field_clear:"",field_placeholder:"每个规则一行 如：".concat(this.type,"://127.0.0.1:8701 100"),field_option:"",field_value:a.data.upstream_list,field_default_value:"",field_unique_name:"upstream_list",field_display_name:"下游服务器ip+权重",field_required:!0,field_valid_rule:"",file_description:"".concat(this.type,"的下游服务器ip+权重")}]}]),this.card_config=this.formatChange([].concat(Object(d["a"])(t),Object(d["a"])(a.data[this.type])),a);case 7:case"end":return e.stop()}}),e,this)})));function i(i){return e.apply(this,arguments)}return i}()}},p=c,m=(l("ee93"),l("cba8")),h=Object(m["a"])(p,a,t,!1,null,"7f706250",null);i["default"]=h.exports}}]);