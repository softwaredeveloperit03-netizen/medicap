import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';

declare let alertify;
@Component({
  selector: 'app-recforcast',
  templateUrl: './recforcast.component.html',
  styleUrls: ['./recforcast.component.css']
})
export class RecforcastComponent implements OnInit {

  constructor(private service: DataAccessService) {
    
  }
  plant_id;
  plant_type;
  ngOnInit(): void {
    this.getMaterialsLog();
 
  }
  results;
    getMaterialsLog() {
    this.service
      .get(
        'planning/micro.php?type=ReceivingForcastQty'
      )
      .subscribe((response) => {
        this.results = response;

      });
  }
}
