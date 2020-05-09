<?php

class BtcConfig
{
    const USERNAME = 'ac17f1';
    const PASSWORD = 'ed3132d9f13fc4c264cc775504';
    const IP       = '172.18.22.173';
    const PORT     = '18888';
    const DEBUG    = false;
	const MASTER_ADDRESS = 'FvhGTxk1kJg6w14npyxxotrjVa2eLoxeWQ'; //主钱包地址
}

class AoJSConfig
{
	const USERNAME = '33F5Hh';
	const PASSWORD = 'GONGzXUEZ2cz';
	const IP       = '172.18.22.173';
	const PORT     = '8888';
	const DEBUG    = false;
	const MASTER_ADDRESS = 'GciJ8A92jkRx3ZZ5VWHfHuNna178xJs237'; //主钱包地址
}

class SluConfig
{
	//[release]
	const USERNAME = '33F5Hh';
	const PASSWORD = 'GONGzXUEZ2cz';
	const IP       = '172.18.22.173';
	const PORT     = '8899';
	const DEBUG    = false;
	const GRC_ADDRESS = 'SLUVZXdjaZDXVVabWTfoWRjcKSXzTN7krhPQ'; //GRC供转出给用户的总钱包地址
	const IMPORT_RECEIVE_ADDRESS = 'SLUQFCmpvSU1VCBXtwJQBHBePEGx4vcWmuES'; //后台转入金额被接收钱包地址
	
	const CONTRACT = 'b3fe8195c3270717029cd7a881ec6df56052d87a'; //合约地址
	const CONTRACT_TAG = 'GRC'; //代币标识
	const AMOUNT_POINT = 8; //金额精度
	
	
	//datahex 前缀信息
	const BALANCE_OF = '70a08231'; //查询余额
	const TRANSFER_OF = 'a9059cbb'; //转账
	const MINT_OF = '40c10f19'; //挖矿
	const APPROVE_MINER_OF = '6530fa45'; //授权挖矿
	const REMOVE_MINER_OF = "10242590"; //移除挖矿
	const CHANGE_MANAGER_OF = "a3fbbaae"; //修改管理员
	const INITIAL_MANAGER_OF = "d2c0091f"; //初始化管理员
	const CHANGE_OWNER_OF = "a6f9dae1"; //修改所有者
	const RECYCLE_OF = "5d36d182"; //回收
	const MANAGER_OF = "481c6a75"; //管理员
	const OWNER_OF = "8da5cb5b"; //拥有者
	const IS_APPROVE_MINER_OF = "8e786fb4"; //是否可以挖矿
	const TRANSFER_TYPE_OF = "ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef"; //转账主题
	const OUTPUT_OF = "0000000000000000000000000000000000000000000000000000000000000000"; //初始化状态码
	const TRUE_OF = "0000000000000000000000000000000000000000000000000000000000000001"; //true
	const ADD_MANAGER_OF = "2d06177a"; //添加管理员(数据上链)
	const IS_APPROVED_MANAGER_OF = "14605cc4"; //是否是管理员
	const REMOVEMANAGER_OF = "ac18de43"; //移除管理员
	
	//API头信息
	const API_HOST = 'https://sluapi.silubium.org/silubium-api/';
	
	//GAS配置
	const GAS_LIMIT = 500000;
	const GAS_PRICE = 0.0000001;
	
	//钱包转钱包手续费
	const ADDRESS_TO_ADDRESS_FEE = 0.05;
}