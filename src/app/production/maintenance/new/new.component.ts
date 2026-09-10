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

  equipments;
  sections;
  selectedEquipment = [];

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getDepartmentSections();
    this.getEquipments();
  }

  getDepartmentSections(){
    this.service.get('common.php?type=getDepartmentSections').subscribe(response => {
      this.sections = response;
    });
  }

  getEquipments(){
    this.service.get('equipments.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

  getEquipmentsDetails(index){
    index = index - 1;
    if(index !== -1){
      this.selectedEquipment = this.equipments[index];
    }
  }

  saveMaintenanceNote(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.service.post('engineering/maintenance.php?type=saveMaintenance',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Saved Successfully!');
        this.router.navigate(['/maintenance']);
      }else{
        alertify.error('Failed an error occurd,Please try again!');
      }
    });
  }

}
