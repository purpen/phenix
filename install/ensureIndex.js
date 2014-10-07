# 红包管理
db.bonus.ensureIndex({'code':1}, {unique: true}, {background: true});
db.bonus.ensureIndex({'user_id':1}, {background: true});
db.bonus.ensureIndex({'used':1}, {background: true});
db.bonus.ensureIndex({'status':1}, {background: true});


