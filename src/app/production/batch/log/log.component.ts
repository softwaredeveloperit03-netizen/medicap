import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  isView=false
  results:any
  ngOnInit() {
  }
  download() {
     this.service.open('bmr/testPdf/testPdf.php?type=testPdf');
    
 }

}
