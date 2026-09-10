import { Component, OnInit } from '@angular/core';
declare let alertify: any;
import { DataAccessService } from '../data-access.service';

@Component({
  selector: 'app-footer',
  templateUrl: './footer.component.html',
  styleUrls: ['./footer.component.css']
})
export class FooterComponent implements OnInit {
  software_license_no='';
  username='';
  plant_type: string;
  plant_name: string;
  myDate : Date;
  constructor(public service: DataAccessService) {
    setInterval(() => { this.myDate = new Date(); }, 1);
    this.service.observableUsername.subscribe(response => {
      console.log(response);
      this.username = this.service.username;
    });
  }

 
  ngOnInit() {
    this.loadPlantInfo();
  }
  loadPlantInfo() {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_name = this.service.getPlantConfigFields('plant_name');
    this.software_license_no = localStorage.getItem("licence_no");
  }
}
