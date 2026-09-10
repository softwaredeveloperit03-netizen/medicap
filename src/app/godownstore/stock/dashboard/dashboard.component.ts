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
    { id: 'raw', title: 'Raw Material', route: 'raw', icon: 'fa-box-open', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'packing', title: 'Packing Material', route: 'packing', icon: 'fa-box', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'new-stock', title: 'Raw Mat (UNDR DEV)', route: 'NEW STOCK', icon: 'fa-cube', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];



  public rawMaterialmonthly: any;
  public month: any = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  public Raw_Material_Monthly_Purchase_Data: any = ['420100', '223121', '311122', '434221', '553121', '624511', '662516', '321133', '262134', '443621', '203121', '301122'];
  public Packing_Material_Monthly_Purchase_Data: any = ['220100', '423121', '511122', '134221', '253121', '524511', '662516', '421133', '162134', '343621', '603121', '501122'];

  public colorArray:any = ['#FF6633', '#FFB399', '#FF33FF', '#FFFF99', '#00B3E6', 
  '#E6B333', '#3366E6', '#999966', '#99FF99', '#B34D4D',
  '#80B300', '#809900', '#E6B3B3', '#6680B3', '#66991A', 
  '#FF99E6', '#CCFF1A', '#FF1A66', '#E6331A', '#33FFCC',
  '#66994D', '#B366CC', '#4D8000', '#B33300', '#CC80CC', 
  '#66664D', '#991AFF', '#E666FF', '#4DB3FF', '#1AB399',
  '#E666B3', '#33991A', '#CC9999', '#B3B31A', '#00E680', 
  '#4D8066', '#809980', '#E6FF80', '#1AFF33', '#999933',
  '#FF3380', '#CCCC00', '#66E64D', '#4D80CC', '#9900B3', 
  '#E64D66', '#4DB380', '#FF4D4D', '#99E6E6', '#6666FF','#FF6633', '#FFB399', '#FF33FF', '#FFFF99', '#00B3E6', 
  '#E6B333', '#3366E6', '#999966', '#99FF99', '#B34D4D',
  '#80B300', '#809900', '#E6B3B3', '#6680B3', '#66991A', 
  '#FF99E6', '#CCFF1A', '#FF1A66', '#E6331A', '#33FFCC',
  '#66994D', '#B366CC', '#4D8000', '#B33300', '#CC80CC', 
  '#66664D', '#991AFF', '#E666FF', '#4DB3FF', '#1AB399',
  '#E666B3', '#33991A', '#CC9999', '#B3B31A', '#00E680', 
  '#4D8066', '#809980', '#E6FF80', '#1AFF33', '#999933',
  '#FF3380', '#CCCC00', '#66E64D', '#4D80CC', '#9900B3', 
  '#E64D66', '#4DB380', '#FF4D4D', '#99E6E6', '#6666FF'];
  public all:any=[];
  public MaterialSubType:any=[];
  public MaterialTypeQty:any=[];

  public MaterialName:any=[];
  public MaterialQty:any=[];
  public MatColor:any=[];


  constructor(private service: DataAccessService) {
    Chart.register(...registerables);
  }

  ngOnInit() {
  this.getMaterialOutDetails();
  this.MaterialNameQty();


  }






  getMaterialOutDetails() {
    var MaterialSubType:any=[];
    this.service.get('store/bincard.php?type=getMaterials&material_type=Raw Material').subscribe((response) => {
        console.log(response);
        this.all=response;


        // for(let i in this.all){

        //     MaterialSubType.push(this.all[i].material_subtype);
        // }
        

        // console.log(MaterialSubType);

        // MaterialSubType.forEach(element => {
        //     if (!this.MaterialSubType.includes(element)) {
        //       this.MaterialSubType.push(element);
        //     }
        // });
        // console.log(this.MaterialSubType);


        // for(let i in this.all){
          
        // }

        for(let i in this.all){

              this.MaterialName.push(this.all[i].material_name);
              this.MaterialQty.push(this.all[i].received_qty);
              this.MatColor.push(this.colorArray[i]);

        }



    });
  }




    MaterialNameQty(){
      Chart.register(LineController, LineElement, PointElement, LinearScale, Title);

      this.rawMaterialmonthly = new Chart("MaterialWiseMonthlyChart", {
        type: 'bar',

        data: {
          labels: this.MaterialName,
          datasets: [
            {
              label: 'Material',
              data: this.MaterialQty,
              backgroundColor: this.MatColor,
              borderColor: 'black',
              borderWidth: 2
            }

          ]
        },


      });
    }


  }
