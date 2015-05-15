# 红包管理
db.bonus.ensureIndex({'code':1}, {unique: true}, {background: true});
db.bonus.ensureIndex({'user_id':1}, {background: true});
db.bonus.ensureIndex({'used':1}, {background: true});
db.bonus.ensureIndex({'status':1}, {background: true});

db.bonus.ensureIndex({'used':1,'status':1}, {background: true});


# 订单管理
db.ordertemp.ensureIndex({'rid':1}, {unique: true}, {background: true});

db.orders.ensureIndex({'rid':1}, {unique: true}, {background: true});

# 产品管理


# 用户管理
db.user.ensureIndex({'account':1}, {unique: true}, {background: true});
db.user.ensureIndex({'nickname':1}, {unique: true}, {background: true});

db.user.ensureIndex({'sina_uid':1}, {background: true});
db.user.ensureIndex({'qq_uid':1}, {background: true});

db.user.ensureIndex({'state':1, 'mentor':1}, {background: true});

# 手机验证码
db.verify.ensureIndex({'phone':1,'code':1}, {unique: true}, {background: true});



db.emailing.ensureIndex({'email':1}, {unique: true}, {background: true});


db.phones.ensureIndex({'phone':1}, {unique: true}, {background: true});