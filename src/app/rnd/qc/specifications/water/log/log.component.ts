import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  specifications;

  selectedSpec = [];

  water_type = '';
  status = '';

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getSpecificationsLog();
  }

  getSpecificationsLog(){
    this.service.get('qc/specification/water.php?type=getSpecificationsLog&water_type=' + this.water_type + '&status=' + this.status).subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

  


}
