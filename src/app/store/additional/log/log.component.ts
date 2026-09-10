import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getData();
   
  }
    getData() {    
      this.service.get('production/additional_material.php?type=get_request_materials').subscribe(response => {
        this.results = response;
      
      });
    }

    // getissuance() {    
    //   this.service.get('production/additional_material.php?type=getissuance').subscribe(response => {
    //     this.results = response;
      
    //   });
    // }

}
