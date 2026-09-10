import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-direct',
  templateUrl: './direct.component.html',
  styleUrls: ['./direct.component.css']
})
export class DirectComponent implements OnInit {
  vendor;
  materials;
  materialList=[];
  qty=0;
  rate=0;

  gross_amount=0;
  tax_amount=0;
  net_amount=0;

  tax_total=0;
  gross_total = 0;
  gst_total = 0;
  net_total = 0;
  selectedMaterial=[];
  isRaw=false;
  isPacking=false;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getVendor();
  }

  getVendor(){
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendor=response;
    });
  }

  getGeneralMaterials(value) {
    this.service.get('common.php?type=getMaterialsByType&material_type='+value).subscribe(response=>{
      this.materials = response;
      // this.selectedMaterial = [];
      for (let i = 0; i < this.materials.length; i++) {
        let material = this.materials[i];
        material['gross_total'] = 0;
        material['gst_total'] = 0;
        material['net_total'] = 0;
        material['qty'] = 0;
        material['rate'] = 0;
        this.materials[i] = material;
      }
    });
  }

  calculation() {
    if (this.selectedMaterial.length !== 0) {
      this.gross_amount = this.qty * this.rate;
      this.tax_amount = +((+this.gross_amount * +this.selectedMaterial['gst']) / 100);
      this.net_amount = this.gross_amount + this.tax_amount;
    } else {
      this.gross_amount = 0;
      this.tax_amount = 0;
      this.net_amount = 0;
    }
 }

 getGst(index) {
  index = index - 1;
  if (index !== -1) {
    this.gross_amount = 0;
    this.tax_amount = 0;
    this.net_amount = 0;
    this.selectedMaterial = this.materials[index];
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
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['qty'] = this.qty;
    temp['rate'] = this.rate;
    temp['gst']= this.selectedMaterial['gst'];
    console.log(temp['gst']);
    temp['gross_total'] = this.gross_amount;
    temp['gst_total'] = this.tax_amount;
    temp['net_total'] = this.net_amount;
    temp['material_name'] = this.selectedMaterial['material_name'];
    // temp['gst'] = this.selectedMaterial['gst'];
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
    
    this.gross_total += +temp['gross_amount'];
    this.gst_total += +temp['tax_amount'];
    this.net_total += +temp['net_amount'];
    // this.gst_total = parseFloat(this.gst_total + '').toFixed(2);
    this.selectedMaterial = [];
    this.gross_amount = 0;
    this.tax_amount = 0;
    this.net_amount = 0;
    this.rate = 0;
    this.qty = 0;
  }

  del(index){
    let temp=this.materialList[index];
    this.gross_total=this.gross_total*1-temp['gross_amount']*1;
    this.gst_total=this.gst_total*1-temp['tax_amount']*1;
    this.net_total=this.net_total*1-temp['net_amount']*1;
    this.materialList.splice(index, 1);
  }
  
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp=data.value;
    temp['materials']=this.materialList;
    temp['gross_total']=this.gross_total;
    temp['gst_total']=this.gst_total;
    temp['net_total']=this.net_total;
    this.service.post('store/challan.php?type=saveDirectChallan',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
          alertify.success('Data save successfuly');
          data.resetForm();
          this.router.navigate(['/store/challan']);
      }else{
        alertify.error('some error occured!');
      }
    });
  }

}