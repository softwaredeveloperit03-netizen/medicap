import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Chart, LinearScale, LineController, LineElement, PointElement, registerables, Title } from 'chart.js';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'points', title: 'Sampling Points', route: 'points', icon: 'fa-map-marker-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    // { id: 'specification', title: 'Water Specification', route: 'specification', icon: 'fa-clipboard-list', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'plan', title: 'Sampling Points Plan', route: 'plan', icon: 'fa-map-marker-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'sampling-allocation', title: 'Water Sampling Allocation', route: 'sampling-allocation', icon: 'fa-map-marker-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'sampling', title: 'Water Sampling', route: 'sampling', icon: 'fa-map-marker-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'testing', title: 'Allocation', route: 'test/testing', icon: 'fa-tasks', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'test', title: 'Testing', route: 'test', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
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
