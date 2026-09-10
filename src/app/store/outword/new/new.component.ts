import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  qty;
  ar_no='';
  grn_no='';
  materials;
  plant_id='';
  is_corporate='';
  plant_code='';
  material_name;
  m_name='';
  unit='';
  lists=[];
  departments;
  isPickup = false;
  isVehicle = false;
  material_type='';
  units;
  batches=[];
  material_code;
  results;
  transports;
  from_date = '';
  to_date = '';
  today= '';
  department='';
  department_name='';
  selectedResult;

  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getMaterialOutDetails();
    this.getUnits();
    this.getDepartments();
    this.getTransport();
    this.plant_id=localStorage.getItem("plant_id");
    this.is_corporate=localStorage.getItem("is_corporate");
    
  }

  view(i){
    this.selectedResult=this.results[i];
  }

  getMaterialOutDetails() {
    this.service.get('store/outward.php?type=getOutwordLog&from_date=' + this.from_date + '&to_date=' + this.to_date+'&department_name='+ this.department).subscribe(response => {
   this.results = response;
    });
  }
  
  download() {
    this.service.open('store/outward.php?type=downloadOutwordLog&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  // getMaterials() {
  //   this.service.get('common.php?type=getMaterialsByType&material_subtype=' + this.material_subtype)
  //   .subscribe(response => {
  //     this.materials = response;
  //   });
  // }
  getUnits() {
    this.service.get('common.php?type=getUnits')
    .subscribe(response => {
      this.units = response;
    });
  }

  getTransportVal(val) {
    if (val === 'By Transport') {
      this.isPickup = false;
      this.isVehicle = true;
    } else if (val === 'By Courier') {
      this.isVehicle = false;
      this.isPickup = true;
    }
  }

  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.departments=response;
    });
  }

  print(id) {
    this.service.open('print/outward.php?id=' + id);
  }

  AllRecord(){
    this.service.get('store/outward.php?type=getAllOutwordLog').subscribe((response : any) => {
      this.results = response;
    });
    this.department_name='';
  }

  number(value){
    if (isNaN(value)){
      alertify.error('Number 10 digit Only');
      return false;
    }
  }

  getMaterials(value) {
    this.service.get('store/outward.php?type=getMaterials&material_subtype='+value).subscribe(response => {
      this.materials = response;
    });
  }

  getBatch(index){
      this.batches = this.materials[index-1]['grns'];
      // this.material_code = this.materials[index-1]['product_code'];
    
  }
  setName(val){
    this.m_name = this.materials[val-1].material_name;
  }

  getQty(val){
    console.log(this.batches[val-1]);
    this.qty = this.batches[val-1]['qty'];
    this.ar_no = this.batches[val-1]['ar_no'];
    this.grn_no = this.batches[val-1]['grn_no'];
  } 

  addmaterial(data){
    // console.log(this.order_qty , this.totalRequiredQty)
    // // this.checkOrderQty();
    // // console.log(this.order_qty, this.totalRequiredQty);
    // let test = this.totalRequiredQty + this.requiredQty;
    // if(this.order_qty<test){
    //   alertify.error('Required Quantity should be less than Order Quantity');
    //   return;
    // }else{
    //   this.totalRequiredQty += this.requiredQty; //
    // }
    // data.value.requiredQty = this.requiredQty;
    // data.value.product_code = this.product_code;
    // data.value.qty = this.qty;
    // data.value.gross_total= this.gross_total;
    // data.value.disc_total = this.disc_total;
    // data.value.taxable = this.taxable;
    // data.value.net_total= this.net_total;
    // data.value.gst = parseFloat(data.value.gst)*2;
    let temp=data.value;
    temp['material_name']=this.m_name;
    this.lists.push(temp);
    // temp['material_name']=this.batches['material_name'];
    data.reset();
  }


  saveoutward(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value;
    temp['materials']=this.lists;
    this.service.post('store/outward.php?type=saveMaterialOutForm', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {
       
        data.resetForm();
        this.getMaterialOutDetails();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error occured');
      }
    });
    this.lists=[];
  }

  getTransport() {
    if (this.is_corporate == "1") {
      this.service.get('master/transport.php?type=getTransport&plant_id=0').subscribe((response: any) => {
        this.transports = response;
     
      });
    } else {
      this.service.get('master/transport.php?type=getTransport&plant_id='+this.plant_id).subscribe((response: any) => {
        this.transports = response;
     
      });
    }
  }



}
