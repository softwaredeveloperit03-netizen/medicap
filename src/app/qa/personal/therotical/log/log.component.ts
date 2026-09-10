import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  equipments;

  selectedEquipment = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getTheroticalTestLog();
  }

  getTheroticalTestLog(){
    this.service.get('qa/personal.php?type=getTheroticalTestLog').subscribe(response => {
      this.equipments = response;
    });
  }

  selectEquipment(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedEquipment = this.equipments[index];
    }
  }

  view(index) {
    this.selectedEquipment = this.equipments[index];
    this.isView = true;
  }


}
