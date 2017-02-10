# 红包管理
db.bonus.ensureIndex({'code':1}, {unique: true}, {background: true});
db.bonus.ensureIndex({'user_id':1}, {background: true});
db.bonus.ensureIndex({'used':1}, {background: true});
db.bonus.ensureIndex({'status':1}, {background: true});

db.bonus.ensureIndex({'used':1,'status':1}, {background: true});

db.bonus.ensureIndex({'used':1,'status':1,'xname':1}, {background: true});

db.bonus.ensureIndex({ 'user_id':1, 'used':1, 'expired_at':1, 'created_on':-1}, {background: true});


# 订单管理
db.ordertemp.ensureIndex({'rid':1}, {unique: true}, {background: true});

db.orders.ensureIndex({'rid':1}, {unique: true}, {background: true});

db.orders.ensureIndex({'created_on':-1}, {background: true});

# 产品管理


# 用户管理
db.user.ensureIndex({'account':1}, {unique: true}, {background: true});
db.user.ensureIndex({'nickname':1}, {unique: true}, {background: true});

db.user.ensureIndex({'sina_uid':1}, {background: true});
db.user.ensureIndex({'qq_uid':1}, {background: true});

db.user.ensureIndex({'state':1, 'mentor':1}, {background: true});
db.user.ensureIndex({'wx_open_id':1}, {background: true});
db.user.ensureIndex({'created_on':1}, {background: true});

db.user.ensureIndex({'symbol': 1}, {background: true});

# 手机验证码
db.verify.ensureIndex({'phone':1,'code':1}, {unique: true}, {background: true});



db.emailing.ensureIndex({'email':1}, {unique: true}, {background: true});


db.phones.ensureIndex({'phone':1}, {unique: true}, {background: true});

# 附件
db.asset.ensureIndex({'parent_id':1, 'asset_type':1, 'created_on': 1}, {background: true});

db.asset.ensureIndex({'file_id':1}, {background: true});


db.session_random.ensureIndex({'session_id':1, 'kind':1}, {background: true});

# 搜索
db.text_index.ensureIndex({'full':1, 'type':1, 'created_on': -1}, {background: true});
db.text_index.ensureIndex({'target_id':1, 'type':1}, {background: true});

# 积分
db.points.balance.ensureIndex({'balance.exp':1}, {background: true});
db.points.balance.ensureIndex({'created_on':-1}, {background: true});
db.points.balance.ensureIndex({'balance.money':-1}, {background: true});


# 收藏
db.favorite.ensureIndex({'event':1, 'type':1, 'created_on':-1}, {background: true});
db.favorite.ensureIndex({ 'user_id':1, 'target_id':1, 'event':1, 'type':1 }, {background: true});


db.egou.ensureIndex({'eid':1, 'hid':1}, {background: true});

db.egoutask.ensureIndex({'eid':1, 'hid':1}, {background: true});

# 用户签到统计
db.user_sign_stat.ensureIndex({only_index:1}, {unique: true}, {background: true});
db.user_sign_stat.ensureIndex({user_id:1, day:1}, {unique: true}, {background: true});
db.user_sign_stat.ensureIndex({user_id:1, week:1}, {background: true});
db.user_sign_stat.ensureIndex({user_id:1, month:1}, {background: true});

db.user_point_stat.ensureIndex({'state':1, 'kind':1, 'total_point': -1}, {background: true});

# auth_token
db.auth_token.ensureIndex({ 'user_id':1}, {background: true});
db.auth_token.ensureIndex({'ttl':1}, {background: true});

db.session.ensureIndex({'alive':1}, {background: true});

#topic	
db.topic.ensureIndex({ 'last_reply_time': -1 }, {background: true});



#remind	// 消息提醒
db.remind.ensureIndex({'user_id':1, 'created_on':-1}, {background: true});

#user_sign		//用户签到
db.user_sign.ensureIndex({'last_sign_time':-1}, {background: true}); 
db.user_sign.ensureIndex({'sign_times':-1}, {background: true});
db.user_sign.ensureIndex({'exp_count':-1}, {background: true});
db.user_sign.ensureIndex({'money_count':-1}, {background: true});

#comment		// 评论
db.comment.ensureIndex({'target_id':1, 'type':1, 'created_on':-1}, {background: true});
db.comment.ensureIndex({'target_id':1, 'type':1, 'love_count':-1}, {background: true});
db.comment.ensureIndex({'target_id':1, 'type':1, 'user_id':1, 'sku_id':1}, {background: true});   // 商品评价

#attend	// 活动报名表(试用拉票，试用想要预约，H5报名)
db.attend.ensureIndex({'target_id':1, 'user_id':1, 'event':1, 'created_on':-1});

#applitable		// 试用申请表
db.applitable.ensureIndex({'target_id':1, 'type':1, 'created_on':-1});
db.applitable.ensureIndex({'user_id':1, 'target_id':1, 'type':1});

#sign_draw_record // 签到抽奖记录
db.sign_draw_record.ensureIndex({ 'day':1, 'user_id':1, 'target_id':1}, {background: true});
db.sign_draw_record.ensureIndex({ 'event':1}, {background: true});
db.sign_draw_record.ensureIndex({ 'created_on':-1}, {background: true});

#timeline	// 用户动态
db.timeline.ensureIndex({ 'user_id':1, 'created_on':-1}, {background: true});

#points.daily	// 积分每日统计 	// worker任务用到
db.points.daily.ensureIndex({ '_id.day':1, 'done':1}, {background: true});

#points.records	// worker任务查询
db.points.records.ensureIndex({ 'state':1, 'account_state':1}, {background: true});

#user_event	// worker任务查询
db.user_event.ensureIndex({ 'state': 1}, {background: true});

#未创建
#pusher     // 商城app设备统计
db.pusher.ensureIndex({ 'uuid':1}, {unique: true}, {background: true});
#fiu_pusher     // Fiu app设备统计
db.fiu_pusher.ensureIndex({ 'uuid':1}, {unique: true}, {background: true});

# app_user_record   // app用户激活数记录--商城
db.app_user_record.ensureIndex({ 'uuid':1}, {unique: true}, {background: true});

# fiu_user_record   // app用户激活数记录--Fiu
db.fiu_user_record.ensureIndex({ 'uuid':1}, {unique: true}, {background: true});

# app_store_user_stat   // app每日统计--商城
db.app_store_user_stat.ensureIndex({ 'day':1}, {background: true});
db.app_store_user_stat.ensureIndex({ 'week':1}, {background: true});
db.app_store_user_stat.ensureIndex({ 'month':1}, {background: true});

# app_fiu_user_stat   // app每日统计--Fiu
db.app_fiu_user_stat.ensureIndex({ 'day':1}, {background: true});
db.app_fiu_user_stat.ensureIndex({ 'week':1}, {background: true});
db.app_fiu_user_stat.ensureIndex({ 'month':1}, {background: true});

# feedback      // 意见反馈
db.feedback.ensureIndex({ 'created_on':-1}, {background: true});

# ip_black_list     // IP黑名单 
db.ip_black_list.ensureIndex({ 'ip':1}, {background: true});

# notice        // 通知
db.notice.ensureIndex({ 'kind':1, 'created_on':-1 }, {background: true});

# scene_brands      // 品牌
db.scene_brands.ensureIndex({ 'kind':1}, {background: true});
db.scene_brands.ensureIndex({ 'mark':1}, {background: true});
db.scene_brands.ensureIndex({ 'created_on':-1 }, {background: true});

# scene_context     // 语境
db.scene_context.ensureIndex({ 'category_id':1, 'created_on':-1 }, {background: true});
db.scene_context.ensureIndex({ 'category_id':1, 'stick':-1, 'created_on':-1 }, {background: true});

# scene_product     // 情景商品
db.scene_product.ensureIndex({ 'kind':1 }, {background: true});
db.scene_product.ensureIndex({ 'category_id':1 }, {background: true});
db.scene_product.ensureIndex({ 'created_on':-1 }, {background: true});

# scene_product_link    // 情景产品关联
db.scene_product_link.ensureIndex({ 'sight_id':-1 }, {background: true});
db.scene_product_link.ensureIndex({ 'product_id':-1 }, {background: true});
db.scene_product_link.ensureIndex({ 'created_on':-1 }, {background: true});

# scene_scene       // 地盘
db.scene_scene.ensureIndex({ 'user_id':1 }, {background: true});
db.scene_scene.ensureIndex({ 'created_on':-1 }, {background: true});

# scene_sight       // 情景
db.scene_sight.ensureIndex({ 'user_id':1 }, {background: true});
db.scene_sight.ensureIndex({ 'scene_id':1 }, {background: true});
db.scene_sight.ensureIndex({ 'created_on':-1 }, {background: true});

# scene_subject     // 情景专题
db.scene_subject.ensureIndex({ 'created_on':-1 }, {background: true});

# tag       // 标签
db.tag.ensureIndex({ 'name':1 }, {background: true});
db.tag.ensureIndex({ 'index':1 }, {background: true});
db.tag.ensureIndex({ 'total_count':-1 }, {background: true});
db.tag.ensureIndex({ 'created_on':-1 }, {background: true});

# temp_tag       // 临时标签
db.temp_tag.ensureIndex({ 'name':1 }, {background: true});
db.temp_tag.ensureIndex({ 'index':1 }, {background: true});
db.temp_tag.ensureIndex({ 'total_count':-1 }, {background: true});
db.temp_tag.ensureIndex({ 'created_on':-1 }, {background: true});

# support       // 投票记录、app秒杀产品推送提醒记录
db.support.ensureIndex({ 'user_id':1 }, {background: true});
db.support.ensureIndex({ 'target_id':1 }, {background: true});

# third_site_stat       // 第三方网站来源统计
db.third_site_stat.ensureIndex({ 'user_id':1 }, {background: true});
db.third_site_stat.ensureIndex({ 'target_id':1 }, {background: true});
db.third_site_stat.ensureIndex({ 'kind':1 }, {background: true});
db.third_site_stat.ensureIndex({ 'created_on':-1 }, {background: true});

# china_city       // 地址库
db.china_city.ensureIndex({ 'oid':1 }, {background: true});
db.china_city.ensureIndex({ 'pid':1 }, {background: true});
db.china_city.ensureIndex({ 'layer':1 }, {background: true});
db.china_city.ensureIndex({ 'sort':-1 }, {background: true});
db.china_city.ensureIndex({ 'name':1 }, {background: true});

# view_stat       // 导流统计
db.view_stat.ensureIndex({ 'target_id':1 }, {background: true});
db.view_stat.ensureIndex({ 'ip':1 }, {background: true});
db.view_stat.ensureIndex({ 'count':-1 }, {background: true});


db.applitable.count()     23665
db.attend.count()         34188
db.comment.count()        29208
db.remind.count()         43137
db.user_sign.count()      19137

db.auth_token.count()     152082
db.points.balance.count() 311638
db.points.daily.count()   79860
db.points.quota.count()   311674
db.points.records.count() 169156
db.session.count()        16658331
db.timeline.count()       57943
db.user.ext_state.count() 313373
db.user_event.count()     174270

# 1447212939
# 2015-11-11 11:35:39

# 1442048186
# 2015-09-12 16:56:26
db.session.remove({'alive': {'$lt': 1442048186}})
db.auth_token.remove({'ttl': {'$lt': 1442048186}})
