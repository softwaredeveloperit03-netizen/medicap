import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
 import { DataAccessService } from 'src/app/data-access.service';
 import { Chart, LinearScale, LineController, LineElement, PointElement, registerables, Title } from 'chart.js';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'challan', title: 'Challan Entry', route: 'challan', icon: 'fa-file-invoice', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'recving', title: 'Recvng. of Chemicals', route: 'recving', icon: 'fa-truck', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'wehing', title: 'Weighing of Chemicals', route: 'wehing', icon: 'fa-balance-scale', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'gen', title: 'GRN', route: 'gen', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'stock', title: 'Stock Book', route: 'stock', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'recving', title: 'Receiving of Equipment', route: 'recving', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'qc-cleaning', title: 'Equipement Cleaning & Usage', route: 'qc/cleaning', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  public rawMaterialmonthly:any;
  public month:any=['Jan','Feb','Mar','Apr','May','Jun','jul','Aug','Sep','Oct','Nov','Dec'];
  public Raw_Material_Monthly_Purchase_Data:any = ['420100','223121','311122','434221','553121','624511','662516','321133','262134','443621','203121','301122'];
  public Packing_Material_Monthly_Purchase_Data:any =  ['220100','423121','511122','134221','253121','524511','662516','421133','162134','343621','603121','501122'];
  public Finished_Material_Monthly_Purchase_Data:any =  ['320100','123121','211122','334221','453121','324511','562516','221133','362134','543621','103121','201122'];
 
  constructor() {
    Chart.register(...registerables);

  }

  ngOnInit() {

    this.MaterialMonthlypurchase();
  }


  MaterialMonthlypurchase(){
    Chart.register(LineController, LineElement, PointElement, LinearScale, Title);

    this.rawMaterialmonthly = new Chart("MaterialWiseMonthlyChart", {
      type: 'bar', 

      data: {
        labels: this.month,
	       datasets: [
          {
            label :'Raw',
            data: this.Raw_Material_Monthly_Purchase_Data,
            backgroundColor: ['#66994D'],
                borderColor: 'black',
                borderWidth: 2
          },
          {
            label :'Packing',
            data: this.Packing_Material_Monthly_Purchase_Data,
            backgroundColor: ['#FF6633'],
                borderColor: 'black',
                borderWidth: 2
          },
          {
            label :'Finished',
            data: this.Finished_Material_Monthly_Purchase_Data,
            backgroundColor: ['#FF3380' ],
                borderColor: 'black',
                borderWidth: 2
          }
          
        ]
      },
      
      
    });
  }

}
