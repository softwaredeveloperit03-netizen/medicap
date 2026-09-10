import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-dailyverif',
  templateUrl: './dailyverif.component.html',
  styleUrls: ['./dailyverif.component.css']
})
export class DailyverifComponent implements OnInit {

  date: string = null;

  constructor(private service: DataAccessService, private router: Router) { 
    const pipe = new DatePipe('en-US');
    this.date = pipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    
  }

  /* 
  getDailyVerif()
  {
    this.service.get('').subscribe(response => {
      this.dailyVerif = response
    });
  }
  */
}
