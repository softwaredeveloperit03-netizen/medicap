import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Chart, LinearScale, LineController, LineElement, PointElement, registerables, Title } from 'chart.js';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'general', title: 'General Material', route: 'general', icon: 'fa-clipboard', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'chemical', title: 'Chemicals Indend', route: 'chemical', icon: 'fa-flask', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'glassware', title: 'Glassware Indend', route: 'glassware', icon: 'fa-vial', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'raw', title: 'Raw Material', route: 'raw', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'packing', title: 'Packing Material', route: 'packing', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'finish', title: 'Finish Product Indend', route: 'finish', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'service', title: 'Service Indend', route: 'service', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
  ];


  isUser = false;
  isChecker = false;
  isApprover = false;
  constructor(private service: DataAccessService) {
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
    Chart.register(...registerables);

  }

  public rawMaterialmonthly:any;
  public month:any=['Jan','Feb','Mar','Apr','May','Jun','jul','Aug','Sep','Oct','Nov','Dec'];
  public Raw_Material_Monthly_Purchase_Data:any = ['420100','223121','311122','434221','553121','624511','662516','321133','262134','443621','203121','301122'];
  public Packing_Material_Monthly_Purchase_Data:any =  ['220100','423121','511122','134221','253121','524511','662516','421133','162134','343621','603121','501122'];
  public Finished_Material_Monthly_Purchase_Data:any =  ['320100','123121','211122','334221','453121','324511','562516','221133','362134','543621','103121','201122'];
 

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
