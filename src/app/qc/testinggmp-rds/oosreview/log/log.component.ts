import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;



@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  oos_data ;
  selectedOos;
  isView = false;

  ooschecklist_data;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingoos();
     }

  getPendingoos() {
    this.service.get('qc/oos.php?type=getooslog').subscribe(response => {
      this.oos_data = response;
    });
  }

  download() {
    this.service.open('qc/oos.php?type=downloadOosLog');
  }


  viewoos(index){
    this.isView = true;

    this.selectedOos = this.oos_data[index];
   }


}
