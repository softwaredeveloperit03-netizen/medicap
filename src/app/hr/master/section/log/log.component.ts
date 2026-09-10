import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  results;
  results1;
  department='';
  status='';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSections();
    this.getDepartments();
  }

  getSections() {
    this.service.get('hr/section.php?type=getSections&department_name='+this.department+'&status='+this.status).subscribe(response => {
      this.results = response;
    });
  }
  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.results1 = response;
    });

  }

}
