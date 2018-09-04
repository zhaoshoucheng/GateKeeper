<?php
//0.002053
//0.001009
require __DIR__ . '/Collection.php';


$json = '{"00:00":{"2017030116_i_505297590_2017030116_o_603227950":"41.46341200240398","2017030116_i_63461810_2017030116_o_603227810":"40.918135060673265"},"00:30":{"2017030116_i_505297590_2017030116_o_603227950":"53.25596670670943","2017030116_i_63461810_2017030116_o_603227810":"58.16254658064819"},"01:00":{"2017030116_i_505297590_2017030116_o_603227950":"52.22879519705045","2017030116_i_63461810_2017030116_o_603227810":"46.529220011327176"},"01:30":{"2017030116_i_505297590_2017030116_o_603227950":"20.749993006388348","2017030116_i_63461810_2017030116_o_603227810":"38.666666666666664"},"02:00":{"2017030116_i_505297590_2017030116_o_603227950":"54.445454545454545","2017030116_i_63461810_2017030116_o_603227810":"58.30101959072814"},"02:30":{"2017030116_i_505297590_2017030116_o_603227950":"29.795454545454547","2017030116_i_63461810_2017030116_o_603227810":"38.13768115942029"},"03:00":{"2017030116_i_505297590_2017030116_o_603227950":"0","2017030116_i_63461810_2017030116_o_603227810":"59.2093023255814"},"03:30":{"2017030116_i_505297590_2017030116_o_603227950":"11.875","2017030116_i_63461810_2017030116_o_603227810":"31.852941176470587"},"04:00":{"2017030116_i_505297590_2017030116_o_603227950":"20.80952380952381","2017030116_i_63461810_2017030116_o_603227810":"32.81818181818182"},"04:30":{"2017030116_i_505297590_2017030116_o_603227950":"8.344370860927153","2017030116_i_63461810_2017030116_o_603227810":"42.88725359299604"},"05:00":{"2017030116_i_505297590_2017030116_o_603227950":"2.2897016935259384","2017030116_i_63461810_2017030116_o_603227810":"39.84375"},"05:30":{"2017030116_i_505297590_2017030116_o_603227950":"32.15974002143445","2017030116_i_63461810_2017030116_o_603227810":"45.97866871910637","2017030116_i_63461831_2017030116_o_63349551":"66.5"},"06:00":{"2017030116_i_505297590_2017030116_o_603227950":"58.53507110351073","2017030116_i_63461810_2017030116_o_603227810":"32.17272872491316","2017030116_i_63461810_2017030116_o_63349551":"47.5","2017030116_i_63461831_2017030116_o_63349551":"171"},"06:30":{"2017030116_i_505297590_2017030116_o_603227950":"63.65","2017030116_i_63461810_2017030116_o_603227810":"50.659339401748156","2017030116_i_63461810_2017030116_o_63349551":"80.59166666666667","2017030116_i_63461831_2017030116_o_603227810":"66.5","2017030116_i_63461831_2017030116_o_63349551":"159.26920729417068"},"07:00":{"2017030116_i_505297590_2017030116_o_603227950":"63.72026839385162","2017030116_i_63461810_2017030116_o_603227810":"50.377544169523276","2017030116_i_63461810_2017030116_o_63349551":"28.5","2017030116_i_63461831_2017030116_o_603227810":"95","2017030116_i_63461831_2017030116_o_603227950":"76","2017030116_i_63461831_2017030116_o_63349551":"91.83333333333333"},"07:30":{"2017030116_i_505297590_2017030116_o_603227950":"75.86966185837649","2017030116_i_63461810_2017030116_o_603227810":"58.547543379426735","2017030116_i_63461810_2017030116_o_63349551":"47.5","2017030116_i_63461831_2017030116_o_603227810":"69.54","2017030116_i_63461831_2017030116_o_63349551":"114.55882352941177"},"08:00":{"2017030116_i_505297590_2017030116_o_603227950":"81.29494892467152","2017030116_i_505297590_2017030116_o_63349551":"121.38888888888889","2017030116_i_63461810_2017030116_o_603227810":"100.96555584784053","2017030116_i_63461810_2017030116_o_63349551":"66.5","2017030116_i_63461831_2017030116_o_603227810":"191.97916666666666","2017030116_i_63461831_2017030116_o_603227950":"148.83333333333334","2017030116_i_63461831_2017030116_o_63349551":"104.5"},"08:30":{"2017030116_i_505297590_2017030116_o_603227950":"87.77499951171875","2017030116_i_505297590_2017030116_o_63349551":"127.57142857142857","2017030116_i_63349550_2017030116_o_603227810":"256.5","2017030116_i_63349550_2017030116_o_603227950":"237.5","2017030116_i_63461810_2017030116_o_603227810":"69.45287586525443","2017030116_i_63461810_2017030116_o_63349551":"71.07407407407408","2017030116_i_63461831_2017030116_o_603227810":"149.93478260869566","2017030116_i_63461831_2017030116_o_603227950":"180.5","2017030116_i_63461831_2017030116_o_63349551":"180.5"},"09:00":{"2017030116_i_505297590_2017030116_o_603227950":"87.50472483067071","2017030116_i_505297590_2017030116_o_63349551":"61.07142857142857","2017030116_i_63461810_2017030116_o_603227810":"73.23417541890204","2017030116_i_63461831_2017030116_o_603227810":"133","2017030116_i_63461831_2017030116_o_603227950":"135.71428571428572"},"09:30":{"2017030116_i_505297590_2017030116_o_603227950":"88.28319589793682","2017030116_i_505297590_2017030116_o_63349551":"150.8125","2017030116_i_63349550_2017030116_o_603227950":"76","2017030116_i_63461810_2017030116_o_603227810":"79.98673568037523","2017030116_i_63461810_2017030116_o_63349551":"57","2017030116_i_63461831_2017030116_o_603227950":"142.5"},"10:00":{"2017030116_i_505297590_2017030116_o_603227950":"100.08395971050699","2017030116_i_505297590_2017030116_o_63349551":"98.01219512195122","2017030116_i_63461810_2017030116_o_603227810":"68.10107972191983"},"10:30":{"2017030116_i_505297590_2017030116_o_603227950":"95.29680365296804","2017030116_i_505297590_2017030116_o_63349551":"130.625","2017030116_i_63349550_2017030116_o_603227950":"104.5","2017030116_i_63461810_2017030116_o_603227810":"57.327892150112135","2017030116_i_63461831_2017030116_o_603227810":"127.75862068965517","2017030116_i_63461831_2017030116_o_603227950":"133","2017030116_i_63461831_2017030116_o_63349551":"247"},"11:00":{"2017030116_i_505297590_2017030116_o_603227950":"98.21777761427023","2017030116_i_505297590_2017030116_o_63349551":"53.64705882352941","2017030116_i_63349550_2017030116_o_603227810":"161.5","2017030116_i_63461810_2017030116_o_603227810":"68.95134878416319","2017030116_i_63461831_2017030116_o_603227810":"129.02727272727273","2017030116_i_63461831_2017030116_o_63349551":"85.5"},"11:30":{"2017030116_i_505297590_2017030116_o_603227950":"107.1604418192368","2017030116_i_505297590_2017030116_o_63349551":"99.75","2017030116_i_63461810_2017030116_o_603227810":"51.709594707296354","2017030116_i_63461810_2017030116_o_63349551":"57","2017030116_i_63461831_2017030116_o_603227810":"76"},"12:00":{"2017030116_i_505297590_2017030116_o_603227950":"72.37125405268883","2017030116_i_505297590_2017030116_o_63349551":"9.5","2017030116_i_63461810_2017030116_o_603227810":"53.55304595582997","2017030116_i_63461810_2017030116_o_63349551":"57","2017030116_i_63461831_2017030116_o_603227810":"150.56060606060606","2017030116_i_63461831_2017030116_o_603227950":"71.25"},"12:30":{"2017030116_i_505297590_2017030116_o_603227950":"70.4500512619165","2017030116_i_505297590_2017030116_o_63349551":"0","2017030116_i_63349550_2017030116_o_603227950":"199.5","2017030116_i_63461810_2017030116_o_603227810":"45.145913905148056","2017030116_i_63461810_2017030116_o_63349551":"47.5","2017030116_i_63461831_2017030116_o_603227810":"195.49996948242188"},"13:00":{"2017030116_i_505297590_2017030116_o_603227950":"96.70321195202199","2017030116_i_505297590_2017030116_o_63349551":"0","2017030116_i_63461810_2017030116_o_603227810":"46.38131313131313","2017030116_i_63461810_2017030116_o_63349551":"47.5","2017030116_i_63461831_2017030116_o_603227810":"82.78571428571429","2017030116_i_63461831_2017030116_o_603227950":"95"},"13:30":{"2017030116_i_505297590_2017030116_o_603227950":"80.39225125808572","2017030116_i_63461810_2017030116_o_603227810":"57.29684786904942","2017030116_i_63461831_2017030116_o_603227810":"101.73636363636363","2017030116_i_63461831_2017030116_o_603227950":"85.5","2017030116_i_63461831_2017030116_o_63349551":"85.5"},"14:00":{"2017030116_i_505297590_2017030116_o_603227950":"76.38220210727171","2017030116_i_505297590_2017030116_o_63349551":"0","2017030116_i_63461810_2017030116_o_603227810":"43.98699622216163","2017030116_i_63461810_2017030116_o_63349551":"66.5","2017030116_i_63461831_2017030116_o_603227810":"66.5","2017030116_i_63461831_2017030116_o_63349551":"95"},"14:30":{"2017030116_i_505297590_2017030116_o_603227950":"80.61111187678512","2017030116_i_505297590_2017030116_o_63349551":"171","2017030116_i_63461810_2017030116_o_603227810":"75.34441247065674","2017030116_i_63461831_2017030116_o_603227810":"143.30851063829786"},"15:00":{"2017030116_i_505297590_2017030116_o_603227950":"83.35865721495256","2017030116_i_505297590_2017030116_o_63349551":"9.5","2017030116_i_505297590_2017030116_o_63461830":"171","2017030116_i_63349550_2017030116_o_603227950":"161.5","2017030116_i_63461810_2017030116_o_603227810":"61.44788565055904","2017030116_i_63461831_2017030116_o_603227810":"171","2017030116_i_63461831_2017030116_o_603227950":"123.5"},"15:30":{"2017030116_i_505297590_2017030116_o_603227950":"75.51112012993799","2017030116_i_505297590_2017030116_o_63349551":"13.232142857142858","2017030116_i_63461810_2017030116_o_603227810":"77.73214126119808","2017030116_i_63461810_2017030116_o_63349551":"57","2017030116_i_63461831_2017030116_o_603227810":"156.26530612244898"},"16:00":{"2017030116_i_505297590_2017030116_o_603227950":"79.07447427243602","2017030116_i_63461810_2017030116_o_603227810":"45.98315832899024","2017030116_i_63461831_2017030116_o_603227810":"143.6875","2017030116_i_63461831_2017030116_o_603227950":"133"},"16:30":{"2017030116_i_505297590_2017030116_o_603227950":"85.69415957107391","2017030116_i_505297590_2017030116_o_63349551":"57.25675675675676","2017030116_i_63461810_2017030116_o_603227810":"60.34693318136232","2017030116_i_63461831_2017030116_o_603227810":"119.79268292682927","2017030116_i_63461831_2017030116_o_603227950":"171"},"17:00":{"2017030116_i_505297590_2017030116_o_603227950":"75.16585434400119","2017030116_i_505297590_2017030116_o_63349551":"180.5","2017030116_i_63461810_2017030116_o_603227810":"64.0919527294992","2017030116_i_63461810_2017030116_o_63349551":"38"},"17:30":{"2017030116_i_505297590_2017030116_o_603227950":"119.40998687744141","2017030116_i_505297590_2017030116_o_63461830":"38","2017030116_i_63461810_2017030116_o_603227810":"77.99250385718432","2017030116_i_63461831_2017030116_o_603227810":"135.06521739130434"},"18:00":{"2017030116_i_505297590_2017030116_o_603227950":"118.61748446271719","2017030116_i_505297590_2017030116_o_63349551":"61.244680851063826","2017030116_i_505297590_2017030116_o_63461830":"190","2017030116_i_63461810_2017030116_o_603227810":"49.6698074628722","2017030116_i_63461831_2017030116_o_603227810":"171","2017030116_i_63461831_2017030116_o_603227950":"161.5"},"18:30":{"2017030116_i_505297590_2017030116_o_603227950":"125.70045325207548","2017030116_i_505297590_2017030116_o_63349551":"19","2017030116_i_505297590_2017030116_o_63461830":"190","2017030116_i_63349550_2017030116_o_603227950":"237.5","2017030116_i_63461810_2017030116_o_603227810":"48.21159289816151","2017030116_i_63461810_2017030116_o_63349551":"57","2017030116_i_63461831_2017030116_o_603227810":"237.5","2017030116_i_63461831_2017030116_o_603227950":"57"},"19:00":{"2017030116_i_505297590_2017030116_o_603227950":"98.12911808050951","2017030116_i_505297590_2017030116_o_63349551":"9.5","2017030116_i_505297590_2017030116_o_63461830":"38","2017030116_i_63461810_2017030116_o_603227810":"66.27271864375966","2017030116_i_63461831_2017030116_o_603227810":"60.325","2017030116_i_63461831_2017030116_o_603227950":"166.8695652173913"},"19:30":{"2017030116_i_505297590_2017030116_o_603227950":"59.016626201553514","2017030116_i_63349550_2017030116_o_603227950":"95","2017030116_i_63461810_2017030116_o_603227810":"45.690201729106626","2017030116_i_63461831_2017030116_o_603227810":"57"},"20:00":{"2017030116_i_505297590_2017030116_o_603227950":"132.24999420805128","2017030116_i_505297590_2017030116_o_63349551":"0","2017030116_i_63461810_2017030116_o_603227810":"59.42418523849621","2017030116_i_63461831_2017030116_o_603227810":"19"},"20:30":{"2017030116_i_505297590_2017030116_o_603227950":"97.06284153005464","2017030116_i_63461810_2017030116_o_603227810":"47.33969347336713"},"21:00":{"2017030116_i_505297590_2017030116_o_603227950":"85.17603013078137","2017030116_i_505297590_2017030116_o_63349551":"180.5","2017030116_i_63461810_2017030116_o_603227810":"58.91373676385361","2017030116_i_63461831_2017030116_o_603227810":"38"},"21:30":{"2017030116_i_505297590_2017030116_o_603227950":"76.10883162621738","2017030116_i_505297590_2017030116_o_63349551":"199.5","2017030116_i_63461810_2017030116_o_603227810":"65.57925473189935","2017030116_i_63461810_2017030116_o_63349551":"47.5"},"22:00":{"2017030116_i_505297590_2017030116_o_603227950":"105.63261741545142","2017030116_i_505297590_2017030116_o_63349551":"199.5","2017030116_i_63461810_2017030116_o_603227810":"78.01753934224446","2017030116_i_63461831_2017030116_o_603227810":"62.18181818181818"},"22:30":{"2017030116_i_505297590_2017030116_o_603227950":"97.36574693373692","2017030116_i_63349550_2017030116_o_603227950":"228","2017030116_i_63461810_2017030116_o_603227810":"55.35351377544981"},"23:00":{"2017030116_i_505297590_2017030116_o_603227950":"99.42878459583629","2017030116_i_63461810_2017030116_o_603227810":"21.86273874021044","2017030116_i_63461831_2017030116_o_603227810":"47.5"},"23:30":{"2017030116_i_505297590_2017030116_o_603227950":"74.0702678936358","2017030116_i_63461810_2017030116_o_603227810":"26.19402985074627"}}';
$json2 = '{"2017030116_i_505297590_2017030116_o_603227950":{"00:00":"41.46341200240398","00:30":"53.25596670670943","01:00":"52.22879519705045","01:30":"20.749993006388348","02:00":"54.445454545454545","02:30":"29.795454545454547","03:00":"0","03:30":"11.875","04:00":"20.80952380952381","04:30":"8.344370860927153","05:00":"2.2897016935259384","05:30":"32.15974002143445","06:00":"58.53507110351073","06:30":"63.65","07:00":"63.72026839385162","07:30":"75.86966185837649","08:00":"81.29494892467152","08:30":"87.77499951171875","09:00":"87.50472483067071","09:30":"88.28319589793682","10:00":"100.08395971050699","10:30":"95.29680365296804","11:00":"98.21777761427023","11:30":"107.1604418192368","12:00":"72.37125405268883","12:30":"70.4500512619165","13:00":"96.70321195202199","13:30":"80.39225125808572","14:00":"76.38220210727171","14:30":"80.61111187678512","15:00":"83.35865721495256","15:30":"75.51112012993799","16:00":"79.07447427243602","16:30":"85.69415957107391","17:00":"75.16585434400119","17:30":"119.40998687744141","18:00":"118.61748446271719","18:30":"125.70045325207548","19:00":"98.12911808050951","19:30":"59.016626201553514","20:00":"132.24999420805128","20:30":"97.06284153005464","21:00":"85.17603013078137","21:30":"76.10883162621738","22:00":"105.63261741545142","22:30":"97.36574693373692","23:00":"99.42878459583629","23:30":"74.0702678936358"},"2017030116_i_505297590_2017030116_o_63349551":{"08:00":"121.38888888888889","08:30":"127.57142857142857","09:00":"61.07142857142857","09:30":"150.8125","10:00":"98.01219512195122","10:30":"130.625","11:00":"53.64705882352941","11:30":"99.75","12:00":"9.5","12:30":"0","13:00":"0","14:00":"0","14:30":"171","15:00":"9.5","15:30":"13.232142857142858","16:30":"57.25675675675676","17:00":"180.5","18:00":"61.244680851063826","18:30":"19","19:00":"9.5","20:00":"0","21:00":"180.5","21:30":"199.5","22:00":"199.5"},"2017030116_i_505297590_2017030116_o_63461830":{"15:00":"171","17:30":"38","18:00":"190","18:30":"190","19:00":"38"},"2017030116_i_63349550_2017030116_o_603227810":{"08:30":"256.5","11:00":"161.5"},"2017030116_i_63349550_2017030116_o_603227950":{"08:30":"237.5","09:30":"76","10:30":"104.5","12:30":"199.5","15:00":"161.5","18:30":"237.5","19:30":"95","22:30":"228"},"2017030116_i_63461810_2017030116_o_603227810":{"00:00":"40.918135060673265","00:30":"58.16254658064819","01:00":"46.529220011327176","01:30":"38.666666666666664","02:00":"58.30101959072814","02:30":"38.13768115942029","03:00":"59.2093023255814","03:30":"31.852941176470587","04:00":"32.81818181818182","04:30":"42.88725359299604","05:00":"39.84375","05:30":"45.97866871910637","06:00":"32.17272872491316","06:30":"50.659339401748156","07:00":"50.377544169523276","07:30":"58.547543379426735","08:00":"100.96555584784053","08:30":"69.45287586525443","09:00":"73.23417541890204","09:30":"79.98673568037523","10:00":"68.10107972191983","10:30":"57.327892150112135","11:00":"68.95134878416319","11:30":"51.709594707296354","12:00":"53.55304595582997","12:30":"45.145913905148056","13:00":"46.38131313131313","13:30":"57.29684786904942","14:00":"43.98699622216163","14:30":"75.34441247065674","15:00":"61.44788565055904","15:30":"77.73214126119808","16:00":"45.98315832899024","16:30":"60.34693318136232","17:00":"64.0919527294992","17:30":"77.99250385718432","18:00":"49.6698074628722","18:30":"48.21159289816151","19:00":"66.27271864375966","19:30":"45.690201729106626","20:00":"59.42418523849621","20:30":"47.33969347336713","21:00":"58.91373676385361","21:30":"65.57925473189935","22:00":"78.01753934224446","22:30":"55.35351377544981","23:00":"21.86273874021044","23:30":"26.19402985074627"},"2017030116_i_63461810_2017030116_o_63349551":{"06:00":"47.5","06:30":"80.59166666666667","07:00":"28.5","07:30":"47.5","08:00":"66.5","08:30":"71.07407407407408","09:30":"57","11:30":"57","12:00":"57","12:30":"47.5","13:00":"47.5","14:00":"66.5","15:30":"57","17:00":"38","18:30":"57","21:30":"47.5"},"2017030116_i_63461831_2017030116_o_603227810":{"06:30":"66.5","07:00":"95","07:30":"69.54","08:00":"191.97916666666666","08:30":"149.93478260869566","09:00":"133","10:30":"127.75862068965517","11:00":"129.02727272727273","11:30":"76","12:00":"150.56060606060606","12:30":"195.49996948242188","13:00":"82.78571428571429","13:30":"101.73636363636363","14:00":"66.5","14:30":"143.30851063829786","15:00":"171","15:30":"156.26530612244898","16:00":"143.6875","16:30":"119.79268292682927","17:30":"135.06521739130434","18:00":"171","18:30":"237.5","19:00":"60.325","19:30":"57","20:00":"19","21:00":"38","22:00":"62.18181818181818","23:00":"47.5"},"2017030116_i_63461831_2017030116_o_603227950":{"07:00":"76","08:00":"148.83333333333334","08:30":"180.5","09:00":"135.71428571428572","09:30":"142.5","10:30":"133","12:00":"71.25","13:00":"95","13:30":"85.5","15:00":"123.5","16:00":"133","16:30":"171","18:00":"161.5","18:30":"57","19:00":"166.8695652173913"},"2017030116_i_63461831_2017030116_o_63349551":{"05:30":"66.5","06:00":"171","06:30":"159.26920729417068","07:00":"91.83333333333333","07:30":"114.55882352941177","08:00":"104.5","08:30":"180.5","10:30":"247","11:00":"85.5","13:30":"85.5","14:00":"95"}}';

$dataByHour = Collection::make(json_decode($json, true));
$dataByFlow = Collection::make(json_decode($json2, true));

$maxFlowIds = $dataByHour->reduce(function ($carry, $item){
    return Collection::make($item)->keysOfMaxValue()->reduce(function (Collection $ca, $it) {
        $ca->increment($it); return $ca;
    }, $carry);
}, Collection::make([]))->keysOfMaxValue()->reduce(function (Collection $carry, $item) use ($dataByHour) {
    return $carry->set($item, $dataByHour->avg($item));
}, Collection::make([]))->keysOfMaxValue();

//找出均值最大的方向的最大值最长持续时间区域
$base_time_box = $maxFlowIds->reduce(function (Collection $carry, $id) use ($dataByFlow, $dataByHour) {
    $maxFlow = Collection::make($dataByFlow->get($id));
    $maxFlowFirstKey = $maxFlow->first(null);
    $maxArray = $nowArray = [
        'start_time' => $maxFlowFirstKey,
        'end_time' => $maxFlowFirstKey,
        'length' => 0,
    ];
    $maxFlow->each(function ($quota, $hour) use ($dataByHour, &$nowArray, &$maxArray) {
        $max = max($dataByHour->get($hour));
        if($quota >= $max && $quota > 0) {
            $nowArray['end_time'] = $hour;
            $nowArray['start_time'] = $nowArray['start_time'] ?? $hour;
            $nowArray['length']++;
        } else {
            if($nowArray['length'] > $maxArray['length']) $maxArray = $nowArray;
            $nowArray = [ 'start_time' => null, 'end_time' => null, 'length' => 0, ];
        }
    });
    if($nowArray['length'] < $maxArray['length']) $nowArray = $maxArray;
    if($carry->isEmpty() || $carry->get('0.length', 0) == $nowArray['length']) {
        return $carry->set($id, $nowArray);
    } elseif($carry->get('0.length', 0) < $nowArray['length']) {
        return Collection::make([$id => $nowArray]);
    } else {
        return $carry;
    }
}, Collection::make([]));

//如果某个时间点某个方向没有数据，则设为 null
$hours = Collection::make($hours);
$dataByFlow = $dataByFlow->map(function ($flow) use ($hours) {
    return $hours->reduce(function ($carry, $item) {
        $carry[$item] = $carry[$item] ?? null; return $carry;
    }, $flow);
});

$dataByFlow->each(function ($value, $key) use (&$base, &$flow_info, &$maxFlowIds) {
    foreach ($value as $k => $v) { $base[$key][] = [$v === null ? null : $this->quotas[$key]['round']($v), $k]; }
    $flow_info[$key] = [ 'name' => $flowsName[$key] ?? '', 'highlight' => (int)($maxFlowIds->inArray($key))];
});

$base_time_box->each(function ($v, $k) use (&$describes, &$summarys, $key, $junctionInfo) {
    $describes[] = $this->quotas[$key]['describe']([
        $junctionInfo['junction']['name'] ?? '',
        $junctionInfo['flows'][$k] ?? '',
        $v['start_time'],
        $v['end_time']]);
    $summarys[] = $this->quotas[$key]['summary']([
        $v['start_time'],
        $v['end_time'],
        $junctionInfo['flows'][$k] ?? '']);
});

$describe_info = implode("\n", $describes);
$summary_info = implode("\n", $summarys);