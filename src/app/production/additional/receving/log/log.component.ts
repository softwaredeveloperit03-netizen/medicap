import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  results;

  ngOnInit() {
    this.getData();
  }
  getData() {
   
    this.service.get('production/additional_material.php?type=get_receving').subscribe(response => {
      this.results = response;
    
    });
  }

}
