import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  vendor;
  materials;
  standardList=[];
  qty=0;
  rate=0;
  units;
  gross_amount=0;
  tax_amount=0;
  net_amount=0;
  gsts;
  tax_total=0;
  gross_total = 0;
  gst_total = 0;
  gst='';
  net_total = 0;
  selectedMaterial=[];
  isRaw=false;
  isPacking=false;
  chemical;
  standards;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getVendor();
    this.getGst();
    this.getStandards();
  }

  getVendor(){
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendor=response;
    });
  }

  getGst(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gsts=response;
    });
  }

  getStandards(){
    this.service.get('qc/standard/master.php?type=getMasters').subscribe(response => {
      this.standards = response;

      // for (let i = 0; i < this.chemical.length; i++) {
      //   let material = this.chemical[i];
      //   material['gross_total'] = 0;
      //   material['gst_total'] = 0;
      //   material['net_total'] = 0;
      //   material['qty'] = 0;
      //   material['rate'] = 0;
      //   this.chemical[i] = material;
      // }
    });
  }

 
  calculation() {
      this.gross_amount = this.qty * this.rate;
      console.log( this.gross_amount);
      this.tax_amount = +((+this.gross_amount * +this.gst) / 100);
      this.net_amount = this.gross_amount + this.tax_amount;
    
 }

 getGstt(index) {
  index = index - 1;
  if (index !== -1) {
    this.selectedMaterial = this.chemical[index];
    console.log(this.selectedMaterial);
  } else {
    this.selectedMaterial = [];
  }
}

getMaterial(material){
  if(material=='Raw Material'){
    this.isRaw=true;
    this.isPacking=false;
  }else if(material="Packing Material"){
    this.isPacking=true;
    this.isRaw=false;
  }
}



  add(data) {
    let temp = data.value;
    temp['qty'] = this.qty;
    temp['rate'] = this.rate;
    temp['gross_total'] = this.gross_amount;
    temp['gst_total'] = this.tax_amount;
    temp['net_total'] = this.net_amount;
    temp['gross_amount'] = this.gross_amount;
    temp['tax_amount'] = this.tax_amount;
    temp['net_amount'] = this.net_amount;
    temp['chemical_name'] = this.selectedMaterial['chemical_name'];
   
    this.standardList[this.standardList.length] = temp;
    data.resetForm();
    
    this.gross_total += +this.gross_amount;
    this.gst_total += +this.tax_amount;
    this.net_total += +this.net_amount;
    this.selectedMaterial = [];
    this.gross_amount = 0;
    this.tax_amount = 0;
    this.net_amount = 0;
    this.rate = 0;
    this.qty = 0;
  }




  del(index){
    let temp=this.standardList[index];
    this.gross_total=this.gross_total*1-temp['gross_amount']*1;
    this.gst_total=this.gst_total*1-temp['tax_amount']*1;
    this.net_total=this.net_total*1-temp['net_amount']*1;
    this.standardList.splice(index, 1);
  }
  
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    if (!this.standardList || this.standardList.length === 0) {
      alertify.error('Add at least one standard line before save');
      return;
    }
    let temp=data.value;
    temp['standards']=this.standardList;
    temp['gross_total']=this.gross_total;
    temp['gst_total']=this.gst_total;
    temp['net_total']=this.net_total;
    this.service.postJson('qc/standard/receiving.php?type=saveChallan', JSON.stringify(temp)).subscribe({
      next: (response: any) => {
        let result = response;
        if (typeof response === 'string') {
          try { result = JSON.parse(response); } catch (e) { result = {}; }
        }
        if (result?.status === 'success') {
          alertify.success('Data save successfuly');
          data.resetForm();
          this.standardList = [];
          this.gross_total = 0;
          this.gst_total = 0;
          this.net_total = 0;
          this.router.navigate(['/qc/standard/receiving/challan']);
        } else {
          alertify.error(result?.message || result?.status || 'some error occured!');
        }
      },
      error: () => alertify.error('Failed to save challan')
    });
  }

}