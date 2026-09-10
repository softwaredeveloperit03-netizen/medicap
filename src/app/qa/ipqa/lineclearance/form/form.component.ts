import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {
  isNew = false;
  requests;
  materials;
  equipments;
  equipment_codes;
  isView = false;
  entries;
  isSanitization = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getLineClearanceRequest();
    this.getEquipments();
  }

  getLineClearanceRequest() {
    this.service.get('qa.php?type=getLineClearanceRequest').subscribe(response => {
      this.requests = response;
    });
  }

  getMaterials(index) {
    this.materials = this.requests[index];
    this.isView = true;
  }

  getEquipments() {
    this.service.get('equipments.php?type=getEquipments').subscribe(response => {
      this.equipments = response;
    });
  }

  getEquipmentID(index) {
    index = index - 1;
    if (index != -1) {
      this.equipment_codes = this.equipments[index].equipments;
    }
  }

  checkSanitization(value) {
    if (value === 'Yes') {
      this.isSanitization = true;
    } else {
      this.isSanitization = false;
    }
  }

  saveCleaningForm(data) {
    this.isView = false;
    let temp = data.value;
    temp["id"] = this.materials['id'];
    temp['clearance_no'] = this.materials["clearance_no"];
    temp['department'] = this.materials['department'];
    temp['section'] = this.materials['section'];
    temp['checkpoints'] = this.materials['checkpoints'];
    
    this.service.post('qaDepartment.php?type=saveCleaningForm', JSON.stringify(temp))
    .subscribe(response => {
      if(response['status'] === 'success') {
        alert('saved successfully');
        data.resetForm();
        this.isView = false;
        this.isNew = false;
        this.getLineClearanceRequest();
      } else {
        alert('An error has occurred, Please try Again');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

}
