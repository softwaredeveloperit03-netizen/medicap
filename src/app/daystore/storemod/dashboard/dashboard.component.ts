import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'item', title: 'Request Material', route: '', icon: 'fa-hand-holding-medical', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'item', title: 'Stock Book', route: '', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'item', title: 'Despensing', route: '', icon: 'fa-pills', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];


  constructor(private route: ActivatedRoute) { }

  dayStoreName = localStorage.getItem('dayStoreName');
  dayStoreCode = localStorage.getItem('dayStoreCode');

  ngOnInit(): void {

    this.dayStoreName = localStorage.getItem('dayStoreName');
    this.dayStoreCode = localStorage.getItem('dayStoreCode');
    
  }

}
