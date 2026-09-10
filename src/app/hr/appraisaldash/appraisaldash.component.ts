import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-appraisaldash',
  templateUrl: './appraisaldash.component.html',
  styleUrls: ['./appraisaldash.component.css']
})
export class AppraisaldashComponent implements OnInit {

  constructor(private router:Router) { }

  ngOnInit(): void {
  }

  goToDashboardPage(path:any){
    if(path == 'performance'){
      this.router.navigate(['/hr/performance']);
    }else if(path == 'Apprisalrequest'){
      this.router.navigate(['/hr/Apprisalrequest']);
    }
  }
}