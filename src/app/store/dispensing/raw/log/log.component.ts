import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
  // results;
  // isView=false;
  // selectedResult=[];
  // selectedMaterial=[];
  // isShow=false;
  // from_date='';
  // product_type='';
  // to_date='';
  // today='';
  // company;
  // company_unit='';
  //   selectedIndex: any;
  //   isDispensingCompleted: boolean;
  unit_conversion;
  isView = false;
  qa_checking = 'Yes';
  prod_checking = 'Yes';
  dispensing_completed = 'No';
  isDispensingCompleted = false;
  results;
  selectedResult = [];
  selectedIndex = -1;
  isStart = false;
  isViewDispensing = false;
  today = '';
  selectedMaterial = [];
  available_ars = [];
  containers = [];
  ars = [];
  // balance_qty = 0;
  balance_qty: any;
  employees;
  gross_wt = 0;
  tare_wt = 0;
  lafs;
  gross_total = 0;
  tare_total = 0;
  net_total = 0;
  dispensing_room;
  employee;
  emp_id = '';
  balances;
  qcperson;
  officer;
  qaperson;
  ar_data = [];
  available_ars_data = [];
  selectedBalance = [];
  selectedLAF = [];
  start_time = '';
  end_time = '';
  start_date: Date;
  end_date: Date;
  pressure_reading = '';
  start_rlaf_time = '';
  rlaf_start;
  end_laf_time = '';
  isViewshow = false;
  selectedview = [];
  selectedContainer = [];
  operator;
  cleaning_from = '';
  cleaning_to = '';
  fifo_method = '';
  plant_id;
  tbqty: number;


  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') }

  ngOnInit(): void {
    this.getAcceptedRequests();
    this.plant_id = this.service.getPlantConfigFields("plant_id");
  }
  formatNumber(): string {
    return this.balance_qty.toFixed(2);
  }
  

  // getDispensingLog(){
  //   this.service.get('store/dispensing.php?type=getDispensingLog&product_type='+this.product_type+'&to_date='+this.to_date+'&from_date='+this.from_date+'$company_unit='+this.company_unit).subscribe(response=>{
  //     this.results=response;
  //   });
  // }
  getAcceptedRequests() {
    this.service.get('store/dispensing.php?type=get_Dispensing_Requests_For_Inprocess_Activity_Formulation&material_type=Raw Material&rpt_type=Request').subscribe(response => {
      this.results = response;
      if (this.results.length > 0) {
        if (this.selectedIndex !== -1) {
          this.selectedResult = this.results[this.selectedIndex];
          this.isView = true;
        } else {
          this.isStart = false;
          this.isView = false;
        }
      } else {
        this.isStart = false;
        this.isView = false;
      }
    });
  }
  close(){
    this.isStart= false;
    this.isView=true;
    this.available_ars = [];
  }
  close1(){
    this.isStart= false;
    this.isView=true;
    this.isViewDispensing=false
    this.available_ars = [];
  }

  view(index) {
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.selectedResult['materials'] = this.prepareMaterialsData(this.selectedResult['materials']);

    this.isDispensingCompleted = this.selectedResult['rm_disp_completed_by'].length > 0;
    let material = this.selectedResult['materials'];
    for (let i = 0; i < material.length; i++) {
      if ((material[i]['qa_checking'] == 'Yes' && material[i]['qa_status'] == 'Approve') && (material[i]['prod_checking'] == 'Yes' && material[i]['prod_status'] == 'Approve')) {
        material[i]['dispense_status'] = 'Done';
      } else if (material[i]['qa_checking'] == 'Yes' && (material[i]['qa_status'] == 'Approve' && material[i]['prod_checking'] == 'No')) {
        material[i]['dispense_status'] = 'Done';
        material[i]['prod_status'] = 'N/A'
      } else if (material[i]['prod_checking'] == 'Yes' && (material[i]['prod_status'] == 'Approve' && material[i]['qa_checking'] == 'No')) {
        material[i]['dispense_status'] = 'Done';
        material[i]['qa_status'] = 'N/A'
      }
    }
    let dispensingCompleted = 'Yes';
    for (let i = 0; i < material.length; i++) {
      if (material[i]['dispense_status'] != 'Done') {
        dispensingCompleted = 'No';
      }
    }
    this.dispensing_completed = dispensingCompleted;
    this.isView = true;
    this.selectedIndex = index;
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  prepareMaterialsData(materials: any[]) {
    const result = [];
    let serialNumber = 0;
    let previousLotNo = '';
  
    for (const comp of materials) {
      if (comp.lot_no !== previousLotNo) {
        serialNumber++;
      }
  
      const processedComp = {
        ...comp,
        serialNumber: serialNumber,
      };
  
      result.push(processedComp);
      previousLotNo = comp.lot_no;
    }
  
    return result;
  }


  qty1;
  conversionofqty(){

    if(this.unit_conversion == 'Applicable'){

      const massInKg = this.selectedMaterial['batch_qty'];
      const densityInKgPerLiter = this.selectedMaterial['density'];

    

       
        const volumeInLiters = massInKg / densityInKgPerLiter;
        this.qty1 = volumeInLiters;

      


      console.log("Everything");



    }else{
      console.log("nothing");
    }

  }





  calculate_qty_to_dispense(ar_qty, net_qty) {

    let qty_to_dispense = ((Number(this.balance_qty) * Number(ar_qty)) / Number(net_qty)).toFixed(2);
    return qty_to_dispense;
  }
  avbl: number;

  show(index) {

    this.unit_conversion = ' ';
    this.qty1 =  0;

    // this.available_ars_data = []
    let last_container_id = 0;
    this.fifo_method = this.selectedResult['fifo_method'];;
    let material = this.selectedResult['materials'];


    this.selectedMaterial = material[index];
    // this.avbl = this.selectedMaterial['avbl_stock'];
    // this.avbl = parseFloat(this.avbl.toFixed(2));
    this.avbl = parseFloat(this.selectedMaterial['avbl_stock']);
this.avbl = parseFloat(this.avbl.toFixed(2));

    this.available_ars_data = this.selectedMaterial['available_ars'];
    if (this.selectedMaterial['material_code'] == 'RM-017') {
      this.balance_qty = 326;
    } else {
      this.balance_qty = +this.selectedMaterial['batch_qty'];
    }

    let _balQty = this.balance_qty;
    for (let i = 0; i < this.available_ars_data.length; i++) {
      if (this.balance_qty <= 0) {
        break;
      }





      if (this.selectedResult['calculation_type'] == 'LOD Basis') {
        if (Number(this.available_ars_data[i]['dry_qty']) < _balQty) {
          var calc_value = this.calculate_qty_to_dispense(this.available_ars_data[i]['balance_qty'], this.available_ars_data[i]['dry_qty']);
          this.available_ars_data[i]['qty_to_dispense'] = this.available_ars_data[i]['balance_qty'];
          _balQty = (_balQty - Number(this.available_ars_data[i]['dry_qty']));
          this.available_ars_data[i]['next_ar_qty'] = _balQty;
        } else {
          this.available_ars_data[i]['qty_to_dispense'] = _balQty;
          this.available_ars_data[i]['next_ar_qty'] = 0;//_balQty - Number( this.available_ars_data[i]['dry_qty']);
          _balQty = 0;
        }
      }

      else if (this.selectedResult['calculation_type'] == 'Assay Basis') {
        if (Number(this.available_ars_data[i]['pure_qty']) < _balQty) {
          var calc_value = this.calculate_qty_to_dispense(this.available_ars_data[i]['balance_qty'], this.available_ars_data[i]['pure_qty']);
          this.available_ars_data[i]['qty_to_dispense'] = this.available_ars_data[i]['balance_qty'];
          _balQty = (_balQty - Number(this.available_ars_data[i]['pure_qty']));
          this.available_ars_data[i]['next_ar_qty'] = _balQty;
        } else {
          let rev_calc = ((Number(this.available_ars_data[i]['balance_qty']) * _balQty) / Number(this.available_ars_data[i]['pure_qty'])).toFixed(2);
          this.available_ars_data[i]['qty_to_dispense'] = rev_calc;
          this.available_ars_data[i]['next_ar_qty'] = 0;
          _balQty = 0;
        }
      } else {
        if (Number(this.available_ars_data[i]['balance_qty']) < _balQty) {
          this.available_ars_data[i]['qty_to_dispense'] = Number(this.available_ars_data[i]['balance_qty']);
          _balQty = (_balQty - Number(this.available_ars_data[i]['balance_qty']));
          this.available_ars_data[i]['next_ar_qty'] = _balQty;
        } else {
          this.available_ars_data[i]['qty_to_dispense'] = this.available_ars_data[i]['balance_qty'];
          this.available_ars_data[i]['next_ar_qty'] = _balQty - Number(this.available_ars_data[i]['balance_qty']);;
        }
      }

    }
  
let bqt = 0;

    for (let i = 0; i < this.available_ars_data.length; i++) {

      bqt += this.available_ars_data[i]['balance_qty'];

      console.log(bqt);
    }
    console.log(bqt);
    this.tbqty=parseFloat(bqt.toFixed(2));
    this.gross_total = 0;
    this.net_total = 0;


let desp_qty = 0;
    
 desp_qty = parseFloat(this.selectedMaterial['batch_qty']);

 
this.net_total = desp_qty;


for (let i = 0; i < this.available_ars_data.length && desp_qty > 0; i++) {
  let ar__qty = +this.available_ars_data[i]['balance_qty'];
  if(ar__qty<0){
    ar__qty=0;
  }
  const currentArno = { ...this.available_ars_data[i] };
  let each_container_size = (currentArno['pack_size'])
    if (currentArno.balance_qty === 0) continue;

    if (desp_qty >= parseFloat(currentArno.balance_qty)) {

      console.log(desp_qty >= currentArno.balance_qty);

      console.log(currentArno.balance_qty);

      if(currentArno.balance_qty > 0){
        desp_qty -= currentArno.balance_qty;
        currentArno.despensedQty =  currentArno.balance_qty;
       currentArno.net_weight = currentArno.balance_qty;
       currentArno.container_no = last_container_id + 1;
      
       currentArno.container_type= "intact"
        currentArno.qty = 0;
        
        this.available_ars.push(currentArno);
        console.log(this.available_ars);
        console.log("bye");

      }else{
        continue;
      }
    
    } else {
      if(currentArno.balance_qty > 0){
        currentArno.qty -= desp_qty;
        currentArno.despensedQty =   desp_qty;
        currentArno.net_weight = currentArno.despensedQty;
        
        currentArno.container_type= "Loose"
        
        currentArno.container_no = last_container_id + 1;
        desp_qty = 0;
        this.available_ars.push(currentArno);
        console.log("hii");
      }else{
        continue;
      }
    }

    
}
     console.log(this.available_ars);
          

    ////////////////////below old code for dispending /////////////////////////////

    // for (let i = 0; i < this.available_ars_data.length; i++) {
    //   if (this.balance_qty <= 0) {
    //     console.log('11');
    //     break;
    //   }


    //   var intact_containers: number = +this.available_ars_data[i]['intact_containers'];
    //   let loose_qty = +this.available_ars_data[i]['loose_Qty'];
      // let ar__qty = +this.available_ars_data[i]['balance_qty'];
      // if(ar__qty<0){
      //   ar__qty=0;
      // }
    //   console.log(ar__qty)
    //   console.log(this.balance_qty)
    //   console.log('----------------')
    //   if (ar__qty > this.balance_qty) {
    //     console.log('12');
    //     console.log(this.balance_qty);
    //     console.log(this.available_ars_data[i]['intact_containers']);


    //     for (let x = 0; x < this.available_ars_data[i]['intact_containers']; x++) {
    //       let each_container_size = (this.available_ars_data[i]['pack_size'])
    //       if (this.balance_qty > 0 && ar__qty>0 ) {
    //         console.log('112');
            
    //         let obj = {
    //             "ar_no": this.available_ars_data[i]['ar_no'],
    //             "container_no": last_container_id + 1,
    //             "container_type": "Loose",
    //             "gross_wt": 0,
    //             "tare_wt": 0,
    //             "net_weight": 0
    //         };
        
    //         // Convert balance_qty to a floating-point number
    //         const balance_qty = parseFloat(this.balance_qty);
        
    //         // Calculate the formatted net weight with two decimal places
    //         const formattedNetWeight = (balance_qty).toFixed(2);
        
    //         // Update net_weight with the formatted value
    //         obj.net_weight = parseFloat(formattedNetWeight);
            
    //         this.available_ars.push(obj);
        
    //         // Update gross_total and net_total with the integer and formatted fractional parts
    //         this.gross_total = (Number(this.gross_total) + Math.floor(balance_qty));
    //         this.net_total = (Number(this.net_total) + parseFloat(formattedNetWeight));
        
    //         // Update balance_qty to be the integer part
    //         this.balance_qty = Math.floor(balance_qty);
            
    //         last_container_id = last_container_id + 1;
    //         console.log('1');
    //         console.log('this.balance_qty > 0 &&ar__qty>0');
    //         console.log(this.available_ars);
    //         break;
    //     }
        
        

    //       else if (this.balance_qty <= 0) {
    //         console.log('121');
    //         console.log('this.balance_qty <= 0');
          
    //       }
    //      else if (this.balance_qty > each_container_size) {
    //         let obj = {
    //           "ar_no": this.available_ars_data[i]['ar_no'],
    //           "container_no": last_container_id + 1,
    //           "container_type": "Intact",
    //           "gross_wt": 0,
    //           "tare_wt": 0,
    //           "net_weight": each_container_size
    //         }
    //         this.available_ars.push(obj);
    //         this.gross_total = (Number(this.gross_total) + Number(each_container_size));
    //         this.net_total = (Number(this.net_total) + Number(each_container_size));
    //         this.balance_qty = (Number(this.balance_qty) - Number(each_container_size));
    //         last_container_id = last_container_id + 1;
    //         console.log('101');
    //         console.log(this.available_ars);
    //         console.log('this.balance_qty > each_container_size');
    //       } else {
    //         let obj = {
    //           "ar_no": this.available_ars_data[i]['ar_no'],
    //           "container_no": last_container_id + 1,
    //           "container_type": "Loose",
    //           "gross_wt": 0,
    //           "tare_wt": 0,
    //           "net_weight": Number(this.balance_qty).toFixed(2)
    //         }
    //         this.available_ars.push(obj);

    //         this.gross_total = (Number(this.gross_total) + Number(this.balance_qty));
    //         this.net_total = (Number(this.net_total) + Number(this.balance_qty));


    //         this.balance_qty = 0;
    //         console.log(this.selectedMaterial['avbl_stock']);
    //         console.log(this.balance_qty);
    //         console.log(this.avbl);
    //         console.log('2');
    //         console.log('else');
    //         console.log('hi');
    //         console.log(this.available_ars);
    //         console.log('hello');
    //       }
    //     } 
    //     // ///////////////////////INTACT=0s////////////////////
    // //     if(this.available_ars_data[i]['intact_containers']==0){

      
    // //  let obj = {
    // //       "ar_no": this.available_ars_data[i]['ar_no'],
    // //       "container_no": last_container_id + 1,
    // //       "container_type": "Loose",
    // //       "gross_wt": 0,
    // //       "tare_wt": 0,
    // //       "net_weight": 0
    // //   };
  
    // //   // Convert balance_qty to a floating-point number
    // //   const balance_qty = parseFloat(this.balance_qty);
  
    // //   // Calculate the formatted net weight with two decimal places
    // //   const formattedNetWeight = (balance_qty).toFixed(2);
  
    // //   // Update net_weight with the formatted value
    // //   obj.net_weight = parseFloat(formattedNetWeight);
      
    // //   this.available_ars.push(obj);
  
    // //   // Update gross_total and net_total with the integer and formatted fractional parts
    // //   this.gross_total = (Number(this.gross_total) + Math.floor(balance_qty));
    // //   this.net_total = (Number(this.net_total) + parseFloat(formattedNetWeight));
  
    // //   // Update balance_qty to be the integer part
    // //   this.balance_qty = Math.floor(balance_qty);
      
    // //   last_container_id = last_container_id + 1;
    // //   console.log('1');
    // // }
    //   } else {
    //     let container_id = 0;
    //     for (let j = 0; j < intact_containers; j++) {
    //       let obj = {
    //         "ar_no": this.available_ars_data[i]['ar_no'],
    //         "container_no": j + 1,
    //         "container_type": "Intact",
    //         "gross_wt": 0,
    //         "tare_wt": 0,
    //         "net_weight": this.available_ars_data[i]['pack_size']
    //       }
    //       this.available_ars.push(obj);
    //       container_id += 1;
    //       console.log(this.available_ars);
    //       console.log('3');
    //       console.log('else');
    //     }

       
    //      if (loose_qty > 0 && ar__qty < 0) {
    //       let obj = {
    //         "ar_no": this.available_ars_data[i]['ar_no'],
    //         "container_no": container_id + 1,
    //         "container_type": "Loose",
    //         "gross_wt": 0,
    //         "tare_wt": 0,
    //         "net_weight": loose_qty
    //       }
    //       this.available_ars.push(obj);
    //       console.log('5');
    //       console.log('loose_qty > 0 && ar__qty < 0');
    //       console.log(this.available_ars);
    //       break;
    //     }
    //   else  if (loose_qty > 0) {
    //       let obj = {
    //         "ar_no": this.available_ars_data[i]['ar_no'],
    //         "container_no": container_id + 1,
    //         "container_type": "Loose",
    //         "gross_wt": 0,
    //         "tare_wt": 0,
    //         "net_weight": loose_qty
    //       }
    //       this.available_ars.push(obj);
    //       console.log('4');
    //       console.log('else');
    //          console.log(this.available_ars);
    //       break;
    //     }
    //     last_container_id = container_id + 1;

    //     this.balance_qty = (this.balance_qty - ar__qty);
    //     this.gross_total = (this.gross_total + ar__qty);
    //     this.net_total = (this.net_total + ar__qty);
    //     console.log('hhhhh');
    //     console.log('hi');
     
    //     console.log('hello');
    //   }
      
    //   console.log('qqq');

    // }

    ////////////////////above old code for dispending /////////////////////////////
    

    this.isStart = true;
    this.isView = false;
    this.isViewDispensing = false;

    this.getStoreEmployees();

    
  }

  calc_tare_weight(value, idx) {
    if (Number(value) > Number(this.available_ars[idx]['net_weight'])) {
      alertify.error("Tare weight exceed than Net weight");
      return;
    }
    this.available_ars[idx]['gross_wt'] = Number(this.available_ars[idx]['net_weight']) + Number(value);
    this.tare_total = 0;
    this.gross_total = 0;
    this.net_total = 0;
    for (let i = 0; i <= this.available_ars.length; i++) {
      this.gross_total = +(Number(this.gross_total) + Number(this.available_ars[i]['gross_wt']).toFixed(2));
      this.tare_total = +(Number(this.tare_total) + Number(this.available_ars[i]['tare_wt']).toFixed(2));
      this.net_total = +(Number(this.net_total) + Number(this.available_ars[i]['net_weight']).toFixed(2));
    }
  }


  getStoreEmployees() {
    this.service.get('store/dispensing.php?type=getStoreEmployees').subscribe(response => {
      this.employees = response;
    });
  }
  addARdata(data) {
    if (!data.valid) {
      alertify.error("all fields are required");
      return;
    }
    let temp = data.value;
    if (+this.balance_qty > 0) {

    }
    //ar_data
  }

  adddata(data) {
    if (!data.valid) {
      alertify.error("all fields are required");
      return;
    }
    let temp = data.value;
    if (+this.balance_qty > 0) {
      temp['net_wt'] = this.gross_wt - this.tare_wt;
      let qty = +parseFloat((+this.balance_qty - +temp['net_wt']) + '').toFixed(2);
      if (qty >= 0) {
        this.containers[this.containers.length] = temp;
        this.balance_qty = +parseFloat((+this.balance_qty - +temp['net_wt']) + '').toFixed(2);
      } else {
        alertify.error('Balance Qty1:' + this.balance_qty);
      }
    } else {
      alertify.error('Balance Qty2:' + this.balance_qty);
    }

    this.ars = [];

    this.gross_total = 0;
    this.tare_total = 0;
    this.net_total = 0;
    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];
      this.gross_total = this.gross_total + +container['gross_wt'];
      this.tare_total = this.tare_total + +container['tare_wt'];
      this.net_total = this.net_total + +container['net_wt'];

      let flag = 0;
      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          flag = 1;
        }
      }
      if (flag == 0) {
        let temp = {};
        temp['ar_no'] = container['ar_no'];
        temp['qty'] = 0;
        this.ars[this.ars.length] = temp;
      }

      this.gross_total = +parseFloat(this.gross_total + '').toFixed(2);
      this.tare_total = +parseFloat(this.tare_total + '').toFixed(2);
      this.net_total = +parseFloat(this.net_total + '').toFixed(2);
    }

    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];

      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          ar['qty'] = +ar['qty'] + +container['net_wt'];
        }
        this.ars[j] = ar;
      }
    }
    data.reset();
    this.gross_wt = 0;
    this.tare_wt = 0;
  }

  // viewshow(index) {
  //   let material = this.selectedResult['materials'];
  //   this.selectedMaterial = material[index];
  //   //  this.selectedContainer = this.selectedMaterial['containers'];
  //   console.log(this.selectedContainer);
  //   this.service.get('store/dispensing.php?type=get_dispensing_Activity_By_Id&id=' + this.selectedMaterial["dispence_id"]).subscribe(response => {
  //     var data = response;
  //     this.available_ars_data = JSON.parse(data['ars']);
  //     this.available_ars = JSON.parse(data['containers']);
  //     this.isStart = false;
  //     this.isView = false;
  //     this.isViewDispensing = true;
  //   });

  // }
  viewshow(index) {
    let material = this.selectedResult['materials'];
    this.selectedMaterial = material[index];
    console.log(this.selectedContainer);
  
    this.service.get('store/dispensing.php?type=get_dispensing_Activity_By_Id&id=' + this.selectedMaterial["dispence_id"]).subscribe(response => {
      var data = response;
      
      // Assuming data['ars'] and data['containers'] are already objects
      this.available_ars_data = data['ars'];
      this.available_ars = data['containers'];
  
      this.isStart = false;
      this.isView = false;
      this.isViewDispensing = true;
    }, error => {
      console.error("Error fetching dispensing activity:", error);
    });
  }
  

  delData(index) {
    let container = this.containers[index];
    this.gross_total = this.gross_total - +container['gross_wt'];
    this.tare_total = this.tare_total - +container['tare_wt'];
    this.net_total = this.net_total - +container['net_wt'];

    this.balance_qty = +parseFloat((+this.balance_qty + +container['net_wt']) + '').toFixed(2);

    this.containers.splice(index, 1);

    this.ars = [];
    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];
      this.gross_total = +container['gross_wt'];
      this.tare_total = +container['tare_wt'];
      this.net_total = +container['net_wt'];

      let flag = 0;
      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          flag = 1;
        }
      }
      if (flag == 0) {
        let temp = {};
        temp['ar_no'] = container['ar_no'];
        temp['qty'] = 0;
        this.ars[this.ars.length] = temp;
      }
    }

    for (let i = 0; i < this.containers.length; i++) {
      let container = this.containers[i];

      for (let j = 0; j < this.ars.length; j++) {
        let ar = this.ars[j];
        if (ar['ar_no'] == container['ar_no']) {
          ar['qty'] = +ar['qty'] + +container['net_wt'];
        }
        this.ars[j] = ar;
      }
    }
  }

  updateDispensingStatus() {

    let temp = {};
    this.service.post('store/dispensing.php?type=update_dispense_complete_by_store&id=' + this.selectedResult['id'] + '&status=' + status, null).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Dispensing Completed Updated Successfully!');
        this.isDispensingCompleted = true;
        this.isView = false;
        this.getAcceptedRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  saveDispensingForm() {
    // if (this.balance_qty != 0) {
    //   alertify.error('Balance Qty:' + this.balance_qty);
    //   return;
    // }
    if (this.emp_id == '') {
      alertify.error('Plese Select Done By');
      return;
    }

    if(this.plant_id!=67){
          if (this.tare_total == 0) {
            alertify.error('Plese Enter Tare Weight');
            return;
          }
      }
    let temp = {};
    temp['lot_id'] = this.selectedMaterial['id'];
    temp['work_order_id'] = this.selectedResult['id'];
    temp['product_code'] = this.selectedResult['product_code'];
    temp['prod_batch_code'] = this.selectedResult['batch_number'];
    temp['done_by'] = this.emp_id;
    temp['qa_checking'] = this.qa_checking;
    temp['prod_checking'] = this.prod_checking;
    temp['containers'] = this.available_ars;
    //   temp['ars'] = this.ars;
    temp['available_ars'] = this.available_ars_data;
    temp['gross_total'] = this.gross_total;
    temp['net_total'] = this.net_total;
    temp['tare_total'] = this.tare_total;
    temp['material_type'] = this.selectedMaterial['material_type'];
    temp['material_subtype'] = this.selectedMaterial['material_subtype'];
    temp['material_code'] = this.selectedMaterial['material_code'];
    temp['unit'] = this.selectedMaterial['unit'];
    temp['ar_no'] = this.selectedMaterial['ar_no'];
    temp['batch_no'] = this.selectedMaterial['batch_no'];
    temp['dispensing_room'] = this.dispensing_room;
    temp['rlaf_start'] = this.rlaf_start
    temp['pressure_reading'] = this.pressure_reading;

    console.log(temp);

    this.service.post('store/dispensing.php?type=saveDispensingForm&dispensing_no=' + this.selectedMaterial['dispensing_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Material Dispensing Completed!');
        this.isStart = false;
        this.available_ars = [];
        this.ars = [];
        this.containers = [];
        this.selectedMaterial = [];
        temp = [];
        this.getAcceptedRequests();


      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  // print(data) {
  //   this.service.open('store/dispensing.php?type=printLabel&material_code=' + data + '&id=' + this.selectedResult['id']);
  // }

  print(index) {
    let material = this.selectedResult['materials'];


    this.selectedMaterial = material[index];
    this.service.open('store/dispensing.php?type=printLabel&material_code=' + this.selectedMaterial['material_code'] + '&material_name=' + this.selectedMaterial['material_name']+ '&material_grade=' + this.selectedMaterial['gradeName']+ '&product_name=' + this.selectedResult['product_name']+'&batch_number=' + this.selectedResult['batch_number']+'&batch_size=' + this.selectedResult['batch_size'] +'&qa_status=' + this.selectedMaterial['qa_status']+'&prod_status=' + this.selectedMaterial['prod_status']
    +'&disp_id=' + this.selectedMaterial["dispence_id"]);
  }
 

   
   

  

   
   

   
  // download(){
  //   this.service.open('store/dispensing.php?type=downloadDispensingLog&product_type='+this.product_type+'&to_date='+this.to_date+'&from_date='+this.from_date);
  // }

  downloadRecord(){
    this.service.open('store/dispensing.php?type=downloadDispensingRecord&id='+this.selectedResult['id']);
  }

}
