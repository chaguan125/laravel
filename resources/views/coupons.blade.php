<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta
        content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0,user-scalable=no"
        name="viewport"
    />
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <meta content="telephone=no" name="format-detection" />
    <meta content="black" name="apple-mobile-web-app-status-bar-style" />
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>太古通用积分平台</title>
    <link href="../resources/css/style.css" rel="stylesheet" />
    <!-- <link href="css/element.css" rel="stylesheet" /> -->
    <link
        rel="stylesheet"
        href="https://unpkg.com/element-ui/lib/theme-chalk/index.css"
    />
</head>
<body>
<div id="app">
    <!-- 加载动画  -->
    <div class="loaders">
        <div class="loader">
            <div class="loader-inner ball-spin-fade-loader">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
    <!-- 加载动画 结束 -->
</div>

<script type="text/x-template" id="temp">
    <div id="temp">
        <el-menu :default-active="activeIndex" class="el-menu-demo" active-text-color="#fb0604" mode="horizontal" @select="handleSelect">
            <el-menu-item index="1">基础规则</el-menu-item>
            <el-menu-item index="2">其他规则</el-menu-item>
            <el-menu-item index="3">确认信息</el-menu-item>
        </el-menu>
        <div v-if="activeIndex==1" class="main">
            <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="135px" class="demo-ruleForm">
                <el-form-item label="品牌名称" prop="brand_name">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm.brand_name"></el-input>
                    </div>
                </el-form-item>
                <el-form-item label="厂房">
                    <el-select class="ipt" v-model="ruleForm.tenant_id" placeholder="请选择厂房">
                        <el-option label="1厂房" value="1"></el-option>
                        <el-option label="2厂房" value="2"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="商品名称" prop="goods_name">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm.goods_name"></el-input>
                    </div>
                </el-form-item>
                <el-form-item label="商品描述" prop="goods_info">
                    <div class="flex">
                        <el-input class="ipt" type="textarea" v-model="ruleForm.goods_info" placeholder=""></el-input>
                    </div>
                </el-form-item>
                <el-form-item label="商品id" prop="goods_id">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm.goods_id"></el-input><div class="right_text"></div>
                    </div>
                </el-form-item>
                <el-form-item label="营销图片(800*600)">
                    <div class="flex">
                        <el-upload
                            action="coupons/file"
                            list-type="picture-card"
                            :on-remove="imgRemove"
                            :data="{'_token':'{{csrf_token()}}'}"
                            :on-error="imgError">
                            <i class="el-icon-plus"></i>
                        </el-upload>
                    </div>
                </el-form-item>
                <el-form-item label="消费门槛金额" prop="floor_amount">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm.floor_amount"></el-input><div class="right_text">元</div>
                    </div>
                </el-form-item>
                <el-form-item label="代金券面额" prop="amount">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm.amount"></el-input><div class="right_text">元</div>
                    </div>
                </el-form-item>
                <el-form-item label="券变动异步通知地址" prop="nofity_uri">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm.nofity_uri"></el-input>
                    </div>
                </el-form-item>
                <el-form-item>
                    <el-button  type="danger" @click="stepAdd('ruleForm')">下一步</el-button>
                </el-form-item>
            </el-form>
        </div>

        <div v-if="activeIndex==2" class="main">
            <el-form :model="ruleForm2" :rules="rules2" ref="ruleForm2" label-width="135px" class="demo-ruleForm">
{{--                <el-form-item label="有效时间" prop="dateTime">--}}
{{--                    <div class="flex">--}}
{{--                        <el-date-picker--}}
{{--                            class="ipt date_ipt"--}}
{{--                            v-model="ruleForm.dateTime"--}}
{{--                            type="datetime"--}}
{{--                            @change="dateTimeClick"--}}
{{--                            :picker-options="pickerBeginOption"--}}
{{--                            format="yyyy 年 MM 月 dd 日"--}}
{{--                            value-format="yyyy-MM-dd">--}}
{{--                        </el-date-picker>--}}
{{--                    </div>--}}
{{--                </el-form-item>--}}
                <el-form-item label="有效时间" prop="voucher_quantity">
                    <el-input class="ipt" v-model="ruleForm2.duration"></el-input><div class="right_text">天</div>
                </el-form-item>

                <el-form-item label="发放时间段">
                    <el-checkbox-group v-model="ruleForm.voucher_available_time[0].day_rule" fill="#5FB878">
                    </el-checkbox-group>
                    <el-time-picker
                        is-range
                        class="ipt date_ipt"
                        v-model="dayTime"
                        value-format="HH:mm:ss"
                        range-separator="至"
                        start-placeholder="开始时间"
                        end-placeholder="结束时间"
                        placeholder="选择时间范围"
                        @change="dayTimeClick"
                    >
                    </el-time-picker>
                </el-form-item>
                <el-form-item label="业务单号">
                    <el-input class="ipt" v-model="ruleForm2.out_biz_no"></el-input>
                </el-form-item>
                <el-form-item label="发券数量" prop="voucher_quantity">
                    <el-input class="ipt" v-model="ruleForm2.voucher_quantity"></el-input><div class="right_text">个</div>
                </el-form-item>
                <el-form-item label="券使用说明" prop="voucher_description">
                    <el-input class="ipt" type="textarea" v-model="ruleForm2.voucher_description"></el-input>
                </el-form-item>
                <el-form-item label="最大优惠" prop="voucher_discount_limit">
                    <el-input class="ipt" v-model="ruleForm2.voucher_discount_limit"></el-input>
                </el-form-item>
                <el-form-item label="用户领取数量限制" prop="user_give_max">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm2.user_give_max"></el-input><div class="right_text">个</div>
                    </div>
                </el-form-item>
                <el-form-item label="每日发放数量" prop="pre_day_give_max">
                    <div class="flex">
                        <el-input class="ipt" v-model="ruleForm2.pre_day_give_max"></el-input><div class="right_text">个</div>
                    </div>
                </el-form-item>
                <el-form-item>
                    <el-button type="danger" @click="stepDel">上一步</el-button>
                    <el-button type="danger" @click="stepAdd2('ruleForm2')">下一步</el-button>
                    <!-- <el-button @click="resetForm('ruleForm')">重置</el-button> -->
                </el-form-item>
            </el-form>
        </div>

        <div v-if="activeIndex==3" class="main">
            <div class="flex">
                <div class="label_div">品牌名称</div>
                <span class="right_text">[[ruleForm.brand_name]]</span>
            </div>
            <div class="flex">
                <div class="label_div">厂房</div>
                <span class="right_text">[[ruleForm.tenant_id]]</span>
            </div>
            <div class="flex">
                <div class="label_div">商品名称</div>
                <span class="right_text">[[ruleForm.goods_name]]</span>
            </div>
            <div class="flex">
                <div class="label_div">商品描述</div>
                <span class="right_text">[[ruleForm.goods_info]]</span>
            </div>
            <div class="flex">
                <div class="label_div">有效期</div>
                <span class="right_text">[[ruleForm2.duration]]</span>
            </div>

            <div class="flex">
                <div class="label_div">发放时间段</div>
                <span class="right_text">[[ruleForm.voucher_available_time.time_begin]] [[this.ruleForm.voucher_available_time.time_begin ? '至' :'']] [[ruleForm.voucher_available_time.time_end]]</span>

                {{--                <span class="right_text">[[ruleForm2.voucher_available_time[0].time_begin]]-[[ruleForm2.voucher_available_time[0].time_end]]</span>--}}
            </div>
            <div class="flex">
                <div class="label_div">商品id</div>
                <span class="right_text">[[ruleForm.goods_id]]</span>
            </div>
            <div class="flex">
                <div class="label_div">消费门槛金额</div>
                <span class="right_text">[[ruleForm.floor_amount]]</span>
            </div>
            <div class="flex">
                <div class="label_div">代金券面额</div>
                <span class="right_text">[[ruleForm.amount]]</span>
            </div>
            <div class="flex">
                <div class="label_div">券变动异步通知地址</div>
                <span class="right_text">[[ruleForm.nofity_uri]]</span>
            </div>
            <div class="flex">
                <div class="label_div">业务单号</div>
                <span class="right_text">[[ruleForm2.out_biz_no]]</span>
            </div>
            <div class="flex">
                <div class="label_div">发券数量</div>
                <span class="right_text">[[ruleForm2.voucher_quantity]]</span>
            </div>
            <div class="flex">
                <div class="label_div">券使用说明</div>
                <span class="right_text">[[ruleForm2.voucher_description]]</span>
            </div>
            <div class="flex">
                <div class="label_div">最大优惠</div>
                <span class="right_text">[[ruleForm2.voucher_discount_limit]]</span>
            </div>
            <div class="flex">
                <div class="label_div">用户领取数量限制</div>
                <span class="right_text">[[ruleForm2.user_give_max]]</span>
            </div>
            <div class="flex">
                <div class="label_div">每日发放数量</div>
                <span class="right_text">[[ruleForm2.pre_day_give_max]]</span>
            </div>
            <div class="flex">
                <div class="label_div"></div>
                <el-button type="danger" @click="stepDel">上一步</el-button>
                <el-button type="danger" @click="submit('ruleForm','ruleForm2')">确认提交</el-button>
            </div>
        </div>
    </div>
</script>
<script type="text/javascript" src="../resources/js/vue.min.js"></script>
<script type="text/javascript" src="../resources/js/element.js"></script>
<script src="https://ajax.aspnetcdn.com/ajax/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript">
    var vm = new Vue({
        delimiters:['[[',']]'],   //  处理vue和php 模板冲突的问题
        el: "#app",
        template: "#temp",
        data: {
            activeIndex: "1",
            dayTime: "",
            ruleForm: {
                brand_name: "", //品牌名称
                tenant_id: "厂房", //经营消费
                amount: "", //面额
                floor_amount: "", //使用门槛
                dateTime: "",
                voucher_valid_period: {
                    //可用时间
                    type: "ABSOLUTE",
                    start: "",
                    end: "",
                },
                voucher_available_time: [
                    //使用时间段  开始时间  结束时间
                    {
                        day_rule: ["1", "2", "3", "4", "5", "6", "7"],
                        time_begin: "",
                        time_end: "",
                    },
                ],
            },
            rules: {
                //基础规则 表单验证
                brand_name: [
                    { required: true, message: "请输入品牌名称", trigger: "blur" },
                ],
                goods_name: [
                    { required: true, message: "请输入商品名称", trigger: "blur" },
                ],
                goods_id: [
                    { required: true, message: "请输入商品id", trigger: "blur" },
                ],
                amount: [
                    { required: true, message: "请输入代金券面额", trigger: "blur" },
                ],
                floor_amount: [
                    { required: true, message: "请输入消费门槛使用金额", trigger: "blur" },
                ],
            },
            pickerBeginOption: {
                disabledDate: (time) => {
                    return time.getTime() < Date.now() - 1 * 24 * 60 * 60 * 1000;
                },
            },

            ruleForm2: {
                out_biz_no: "", //业务单号
                voucher_quantity: "", //发券数量
                voucher_description: "", //券使用说明
                voucher_discount_limit: "", //最大优惠
                user_give_max: "", //用户领取数量限制
                pre_day_give_max:"", // 每日发放数量
            },
            rules2: {
                out_biz_no: [
                    { required: true, message: "请输入业务单号", trigger: "blur" },
                ],
                voucher_quantity: [
                    { required: true, message: "请输入发券数量", trigger: "blur" },
                ],
                pre_day_give_max: [
                    { required: true, message: "请输入每日发放数量", trigger: "blur" },
                ],
            },
        },
        methods: {
            handleSelect(key, keyPath) {
                //导航切换
                this.activeIndex = key;
            },
            dateTimeClick(e) {
                //可用时间
                this.ruleForm.voucher_valid_period.start = e[0] ;
            },
            dayTimeClick(e) {
                //时间段
                this.ruleForm.voucher_available_time[0].time_begin = e[0];
                this.ruleForm.voucher_available_time[0].time_end = e[1];
            },
            stepAdd(formName) {
                //基础规则下一步
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        this.activeIndex++;
                    } else {
                        console.log("error submit!!");
                        return false;
                    }
                });
            },
            // 上传失败
            imgError(res) {
                this.$message.error("上传失败");
            },
            // 删除图片
            imgRemove(file, fileList) {
                console.log(file, fileList);
            },
            // 处理图片路径
            setImgUrl(imgArr) {
                let arr = [];
                if (imgArr.length > 0) {
                    for (let i = 0; i < imgArr.length; i++) {
                        const element = imgArr[i];
                        arr.push(element.response.data.url);
                        //这个地方根据后台返回的数据进行取值，可以先打印一下
                    }
                    return arr.join();
                } else {
                    return "";
                }
            },
            stepDel() {
                //上一步
                this.activeIndex--;
            },
            stepAdd2(formName) {
                //其他规则 下一步
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        this.activeIndex++;
                    } else {
                        console.log("error submit!!");
                        return false;
                    }
                });
            },
            submit(ruleForm, formName2) {
                //提交
                if (!this.ruleForm.brand_name) {
                    this.$message.warning("品牌名称不能为空");
                    return;
                }
                if (!this.ruleForm.goods_name) {
                    this.$message.warning("商品名称不能为空");
                    return;
                }
                if (!this.ruleForm.goods_id) {
                    this.$message.warning("商品id不能为空");
                    return;
                }
                if (!this.ruleForm.floor_amount) {
                    this.$message.warning("消费门槛金额不能为空");
                    return;
                }
                if (!this.ruleForm.amount) {
                    this.$message.warning("代金券面额不能为空");
                    return;
                }
                if (!this.ruleForm2.out_biz_no) {
                    this.$message.warning("业务单号不能为空");
                    return;
                }
                if (!this.ruleForm2.voucher_quantity) {
                    this.$message.warning("发券数量不能为空");
                    return;
                }
                if (!this.ruleForm2.pre_day_give_max) {
                    this.$message.warning("每日发放数量不能为空");
                    return;
                }

                $.ajax({
                    type: "POST",
                    url:"coupons/post",
                    data:{
                        _method: 'PUT',
                        brand_name:this.ruleForm.brand_name,
                        tenant_id: this.ruleForm.tenant_id,
                        goods_name:this.ruleForm.goods_name,
                        goods_info:this.ruleForm.goods_info,
                        goods_id:this.ruleForm.goods_id,
                        floor_amount:this.ruleForm.floor_amount,
                        amount:this.ruleForm.amount,
                        nofity_uri:this.ruleForm.nofity_uri,
                        out_biz_no:this.ruleForm2.out_biz_no,
                        voucher_quantity:this.ruleForm2.voucher_quantity,
                        voucher_description:this.ruleForm2.voucher_description,
                        duration:this.ruleForm2.duration,
                        voucher_discount_limit:this.ruleForm2.voucher_discount_limit,
                        user_give_max:this.ruleForm2.user_give_max,
                        pre_day_give_max:this.ruleForm2.pre_day_give_max,
                        '_token':'{{csrf_token()}}'
                    },
                    // dataType: "JSON",
                    async: false,
                    error: function(request) {
                        alert("Connection error");
                    },
                    success: function(data) {
                        //接收后台返回的结果
                        alert(data);
                    }
                })

            },
        },
    });
</script>
</body>
</html>
