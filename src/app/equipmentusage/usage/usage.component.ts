import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-usage',
  templateUrl: './usage.component.html',
  styleUrls: ['./usage.component.css']
})
export class UsageComponent implements OnInit {

  constructor(private service:DataAccessService,private router : Router) { }
isCode=false;
  ngOnInit(): void {
this.getDetails();
this.getEmployee();
this.getproductName();
  }


  isNew=false;
  openModel(){
    this.isNew=true;
  }
  products;
  data;
  getDetails(){
    this.service.get('common.php?type=get_Equipments&depart='+localStorage.getItem('department')).subscribe((response:any) => {
      this.data = response;
     
    });
  }
  emps;
  getproductName(){
    this.service.get('common.php?type=getproductName&depart='+localStorage.getItem('department')).subscribe((response:any) => {
      this.products = response;
     
    });
  }
  getEmployee(){
    this.service.get('common.php?type=get_Eqgetemployee_byDeptipments&depart='+localStorage.getItem('department')).subscribe((response:any) => {
      this.emps = response;
     
    });
  }



  barcodeValue: string = '';
shiping_add: string = '';

          // all equipment
selectedEquipmentCode: string = '';
selectedEquipment: any = null;
 

onScan(code: string) {
  if (!code) return;

  // Find the equipment in the data array by scanned code
  const matched = this.data.find(d => d.equipment_code === code);

  if (matched) {
    this.selectedEquipment = matched;  // auto-select in dropdown
    // Optional: fetch more details from API if needed
    this.fetchData(code);
  } else {
    alert("Equipment not found!");
  }

  this.barcodeValue = '';  // clear input after scan
}



onSelect() {
  this.selectedEquipment = this.data.find(d => d.equipment_code === this.selectedEquipmentCode);
  console.log("Selected manually:", this.selectedEquipment);
}

// When barcode is scanned
onScanBarcode(code: string) {
  const matched = this.data.find(d => d.equipment_code === code);
  if (matched) {
    this.selectedEquipmentCode = matched.equipment_code; // auto-select dropdown
    this.selectedEquipment = matched;
    console.log("Selected via scan:", this.selectedEquipment);
  } else {
    alert("Equipment not found!");
  }
}

 

equipment_name=0;
equipment_code=0;
fetchData(barcode: string) {
  if (!barcode) return;
  this.service.get('common.php?type=get_Equipments_byCode&equipment_code=' + barcode)
    .subscribe({
      next: (res) => {
         this.equipment_name = res['equipment_name'] || '';
         this.equipment_code = res['equipment_code'] || '';
      },
      error: (err) => {
        console.error("API error:", err);
      }
    });
}
   activityList = [];
   isSaving = false;
addRow(data){
   this.isSaving = true;
  let temp=data.value;
temp['equipment_name']=this.selectedEquipment.equipment_name;
temp['equipment_code']=this.selectedEquipment.equipment_code;
temp['department']=localStorage.getItem('department');
// this.activityList[this.activityList.length]=temp;
  //  console.log(this.activityList);
     this.service.post('equipments.php?type=save_equipment_usage_cleaning_record', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
        this.isSaving = false;
      if (result.status === 'success') {
      
        this.isNew = false;
        
        data.reset();
        alertify.success( 'Saved Successfully');
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
    });
}
}

