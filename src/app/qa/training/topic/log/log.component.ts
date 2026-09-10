import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getsubject();
  }

 
  subject_list;
  getsubject() {
    this.service.get('training.php?type=getsubject').subscribe(response => {
      this.subject_list = response;
    });
  }
 

 


 
 
}
