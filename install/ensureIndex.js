# 红包管理
db.bonus.ensureIndex({'code':1}, {unique: true}, {background: true});
db.bonus.ensureIndex({'user_id':1}, {background: true});
db.bonus.ensureIndex({'used':1}, {background: true});
db.bonus.ensureIndex({'status':1}, {background: true});

db.bonus.ensureIndex({'used':1,'status':1}, {background: true});

db.bonus.ensureIndex({'used':1,'status':1,'xname':1}, {background: true});


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

# 收藏
db.favorite.ensureIndex({'event':1, 'type':1, 'created_on':-1}, {background: true});


db.egou.ensureIndex({'eid':1, 'hid':1}, {background: true});

db.egoutask.ensureIndex({'eid':1, 'hid':1}, {background: true});


db.user_sign_stat.ensureIndex({only_index:1}, {unique: true}, {background: true});

db.user_sign_stat.ensureIndex({user_id:1, day:1}, {unique: true}, {background: true});


db.user_point_stat.ensureIndex({'state':1, 'kind':1, 'total_point': -1}, {background: true});



db.auth_token.ensureIndex({'ttl':1}, {background: true});

db.session.ensureIndex({'alive':1}, {background: true});




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

