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
  //stptemprec;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    //this.getstptemprec();
    this.getEmployeeQualityControl();
    this.getDetails();
  }
    data;
  getDetails(){
    this.service.get('common.php?type=getSECTIONS&department1='+localStorage.getItem('department')).subscribe((response:any) => {
      this.data = response;
     
    });
  }

selectedEquipmentCode: string = '';
selectedEquipment: any = null;
onSelect() {
  this.selectedEquipment = this.data.find(d => d.section_code === this.selectedEquipmentCode);
  console.log("Selected manually:", this.selectedEquipment);
}

// When barcode is scanned
onScanBarcode(code: string) {
  const matched = this.data.find(d => d.section_code === code);
  if (matched) {
    this.selectedEquipmentCode = matched.section_code; // auto-select dropdown
    this.selectedEquipment = matched;
    console.log("Selected via scan:", this.selectedEquipment);
  } else {
    alert("Room not found!");
  }
}


  emps;
   getEmployeeQualityControl() {
    this.service.get('common.php?type=get_Eqgetemployee_byDeptipments&depart='+localStorage.getItem('department'))
      .subscribe((response) => {
        this.emps = response;
      });
  }

    // getstptemprec(){
  //   this.service.get('common.php?type=getstptemprec').subscribe(response =>{
  //       this.stptemprec =response
  //     });
  // }
   tempmon
  isNew;
    addtemprec(data){
    let temp = data.value;
temp['section'] = this.selectedEquipment['section_name'] + '-' + this.selectedEquipment['section_code'];
 temp['department']=localStorage.getItem('department');
    console.log(temp);
    this.service.post('equipments.php?type=save_area_ceaningRecord', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Save Successfully');
            this.isNew=false;
        
        } else {
          alertify.error(response['status']);
        }
      });
  }

}
