import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  checkpoints=[];
  departments;
  checkpoint;
    service: any;
  constructor() { }

  ngOnInit(): void {
    this.getDepartments();
  }
  add(){
alert('stored data')
  }
  download(){

  }
  getDepartments() {
    this.service.get('common.php?type=getNonTechnicalDepartments').subscribe(response => {
      this.departments = response;
    });
  }
}
