import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-labour-exit',
  templateUrl: './labour-exit.component.html',
  styleUrls: ['./labour-exit.component.css']
})
export class LabourExitComponent implements OnInit {
  todaysLabours;
  isNew = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTodaysLabors();
  }

  getTodaysLabors() {
    this.service.get('hrDepartment.php?type=getTodaysLabors')
    .subscribe(response => {
      this.todaysLabours = response;
    });
  }

  exitLabour(id) {
    this.service.get('hrDepartment.php?type=exitLabour&id='+id)
    .subscribe(response => {
      this.getTodaysLabors();
    });
  }

}
