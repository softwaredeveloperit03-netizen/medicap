import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-damage-inspection-qa',
  templateUrl: './damage-inspection-qa.component.html',
  styleUrls: ['./damage-inspection-qa.component.css']
})
export class DamageInspectionQAComponent implements OnInit {
  isRecord = false;
  isRecord1 = false;
  isHomePage = true;
  isNew = false;
  isLog = false;

  damages;
  details;
  total_containers = 0;
  numbers;
  isShow = false;
  isSpecial = false;

  reports;
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getPendingDamageMaterials();
    this.getDamageContainersReport();
  }

  showHome(value) {
    if (value == 'home') {
      this.isHomePage = true;
      this.isNew = false;
      this.isLog = false;
    } else if (value == 'new') {
      this.isHomePage = false;
      this.isNew = true;
      this.isLog = false;
    } else if (value == 'log') {
      this.isHomePage = false;
      this.isNew = false;
      this.isLog = true;
    }
  }

  getDamageContainersReport() {
    this.service.get('qaDepartment.php?type=getDamageContainersReport').subscribe(response => {
      this.reports = response;
      if (Object.keys(this.reports).length == 0) {
        this.isRecord1 = true;
      } else {
        this.isRecord1 = false;
      }
    });
  }

  getPendingDamageMaterials() {
    this.service.get('qaDepartment.php?type=getPendingDamageMaterials')
    .subscribe(response => {
      this.damages = response;
      if (Object.keys(this.damages).length == 0) {
        this.isRecord = true;
      } else {
        this.isRecord = false;
      }
    });
  }

  showInspectionForm(index) {
    this.details = this.damages[index];
    this.total_containers = parseInt(this.details['total_damage']);
    this.numbers = Array(this.total_containers).fill(0).map((x,i)=>i);
    this.isShow = true;
  }

  saveInspection(data) {
    data = data.value;
    data["material_code"] = this.details["material_code"];
    this.service.post('qaDepartment.php?type=saveDamageInspection', JSON.stringify(data))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.getPendingDamageMaterials();
        delete this.damages ;
        delete this.details;
        this.total_containers = 0;
        this.isShow = false;
        this.numbers = Array(0).fill(0).map((x,i)=>i);
      } else {
        alert('An error occured');
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
