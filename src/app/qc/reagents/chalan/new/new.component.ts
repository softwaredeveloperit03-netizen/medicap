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
  materialList=[];
  qty=0;
  rate=0;
  unit;
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
  chemical;
  gst=0;

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getVendor();
    this.getChemical();
    this.getUnit();
  }

  getVendor(){
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendor=response;
    });
  }

  getChemical() {
    this.service.get('qc/indicator.php?type=getIndicatorsLog').subscribe(response => {
      this.chemical = Array.isArray(response) ? response : [];
      for (let i = 0; i < this.chemical.length; i++) {
        let material = this.chemical[i];
        material['gross_total'] = 0;
        material['gst_total'] = 0;
        material['net_total'] = 0;
        material['qty'] = 0;
        material['rate'] = 0;
        this.chemical[i] = material;
      }
    });
  }

  reagentLabel(chem: any): string {
    if (!chem) {
      return '';
    }
    return chem.indicator || chem.chemical_name || chem.material_name || chem.reagent_name || '';
  }

  reagentCode(chem: any): string {
    if (!chem) {
      return '';
    }
    return chem.indicator_no || chem.chemical_no || chem.material_code || '';
  }
  
  getUnit(){
    this.service.get('common.php?type=getUnits').subscribe(response=>{
      this.unit=response;
    });
  }
 

  calculation() {
    if (this.selectedMaterial.length !== 0) {
      this.gross_amount = this.qty * this.rate;
      this.tax_amount = +((+this.gross_amount * +this.gst) / 100);
      console.log('test',this.tax_amount );
      console.log('gst',this.gst );
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
    // this.gross_amount = 0;
    // this.tax_amount = 0;
    // this.net_amount = 0;
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
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['qty'] = this.qty;
    temp['rate'] = this.rate;
    temp['gst'] = this.gst || this.selectedMaterial['gst'] || 0;
    temp['gross_total'] = this.gross_amount;
    temp['gst_total'] = this.tax_amount;
    temp['net_total'] = this.net_amount;
    temp['gross_amount'] = this.gross_amount;
    temp['tax_amount'] = this.tax_amount;
    temp['net_amount'] = this.net_amount;
    temp['material_name'] = this.reagentLabel(this.selectedMaterial);
    temp['indicator'] = temp['material_name'];
    temp['indicator_no'] = temp['indicator_no'] || this.reagentCode(this.selectedMaterial);
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
    this.service.post('qc/indicator.php?type=saveDirectChallan',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
          alertify.success('Data save successfuly');
          data.resetForm();
          this.router.navigate(['/qc/reagents/challan_reagent']);
      }else{
        alertify.error('some error occured!');
      }
    });
  }

}