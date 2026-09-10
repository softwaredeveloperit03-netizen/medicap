import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-calender',
  templateUrl: './calender.component.html',
  styleUrls: ['./calender.component.css']
})
export class CalenderComponent implements OnInit {
  results;
  material_type='';
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getReceivingLog()
  }
  download(){
    this.service.open('store/raw.php?type=downloadRetestCalendar');
  }
  getReceivingLog(){
    this.service.get('store/raw.php?type=getRetestCalendar&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
}
