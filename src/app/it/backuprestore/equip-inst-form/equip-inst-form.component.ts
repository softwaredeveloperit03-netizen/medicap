import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-equip-inst-form',
  templateUrl: './equip-inst-form.component.html',
  styleUrls: ['./equip-inst-form.component.css']
})
export class EquipInstFormComponent implements OnInit {
  isNew = false;
  isView = true;
  constructor(
    private service: DataAccessService,

  ) { }

  ngOnInit(): void {
      this.getequipmetsBackupRecord();
      this.getDepartment();
  }
  departments;
equipments;
    getDepartment() {
    this.service
      .get('common.php?type=getDepartments')
      .subscribe((response) => {
        this.departments = response;
      });
  }
  selectedEquip=[];
    get_Equipments(value) {
    this.service
      .get('common.php?type=get_Equipments&depart='+value)
      .subscribe((response) => {
        this.equipments = response;
      });
  }
  results;
    getequipmetsBackupRecord() {
    this.service
      .get('it/EQbackup.php?type=getequipmetsBackupRecord&depart=')
      .subscribe((response) => {
        this.results = response;
      });
  }
  GetQuipData(i){
    this.selectedEquip=this.equipments[i-1]
  }

  equipInst(){
    this.isNew = true;
  }

save(data){
  let temp=data.value
        console.log('temp :>> ', temp);
      this.service.post('it/EQbackup.php?type=saveServerRoomRecord',JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();

         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     });
    }
}
