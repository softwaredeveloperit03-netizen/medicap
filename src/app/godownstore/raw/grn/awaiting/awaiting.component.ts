import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Chart, LinearScale, LineController, LineElement, PointElement, registerables, Title } from 'chart.js';


declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;
  selectedReport = [];
  grades = [];


  plant_id:any;

  checkPointData;

  getCheckPointData() {
      this.service.get('master/checklist.php?type=getCheckPointByForm&module=Grn&form=GRN Checking').subscribe((response) => {
          this.checkPointData = response;
      });
  }






  // public rawMaterialmonthly:any;
  // public month:any=['Jan','Feb','Mar','Apr','May','Jun','jul','Aug','Sep','Oct','Nov','Dec'];
  // public Raw_Material_Monthly_Purchase_Data:any = ['420100','223121','311122','434221','553121','624511','662516','321133','262134','443621','203121','301122'];
  // public Packing_Material_Monthly_Purchase_Data:any =  ['220100','423121','511122','134221','253121','524511','662516','421133','162134','343621','603121','501122'];
  // public Finished_Material_Monthly_Purchase_Data:any =  ['320100','123121','211122','334221','453121','324511','562516','221133','362134','543621','103121','201122'];
 



  constructor(private service: DataAccessService) { Chart.register(...registerables); }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');

    this.getPendingGRN();
    this.getCheckPointData();
    this.service.observableGrade.subscribe(response => {
      this.grades = response;
      //this.MaterialMonthlypurchase();
    });
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//

  getPendingGRN() {
    this.service.get('store/raw.php?type=getPendingGRN').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  prepareGRN(data) {
    console.log(data)
    if (!data.valid) {
      alertify.error('Please enter all mandatory fields');
      return;
    }
    let grn_grades_list=[];
    let temp = data.value;
    
    let grades = data.value['grn_grade'];
    console.log(grades)
    for(let i=0; i<grades.length;i++){
      let obj = {"grade":grades[i]['grade']};
      grn_grades_list.push(obj);
    }


    temp['id'] = this.selectedReport['id'];
    temp['challan_id'] = this.selectedReport['challan_id'];
    temp['short_qty'] = this.selectedReport['short_qty'];
    temp['vendor_no']=this.selectedReport['vendor_no'];
    temp['material_code']=this.selectedReport['material_code'];
    temp['unit']=this.selectedReport['unit'];
    temp['accept_qty'] = this.selectedReport['accept_qty'];
    temp['reject_qty'] = this.selectedReport['reject_qty'];
    temp['batches']=this.selectedReport['batches'];
    temp['inword_no'] = this.selectedReport['inword_no'];
    temp['checklist'] = this.checkPointData || [];
    // temp['grn_grade'] = grn_grades_list;
    this.service.post('store/raw.php?type=saveGRN', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('GRN Prepared successfully');
        this.isView = false;
        this.getPendingGRN();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  viewfile(url) {
    url = this.service.url + '../..upload/coa/' + url;
    window.open(url, '_blank');
  }

  viewChallan(url) {
    url = this.service.url + '../../upload/challan/' + url;
    window.open(url, '_blank');
  }


  // MaterialMonthlypurchase(){
  //   Chart.register(LineController, LineElement, PointElement, LinearScale, Title);

  //   this.rawMaterialmonthly = new Chart("MaterialWiseMonthlyChart", {
  //     type: 'bar', 

  //     data: {
  //       labels: this.month,
	//        datasets: [
  //         {
  //           label :'Raw',
  //           data: this.Raw_Material_Monthly_Purchase_Data,
  //           backgroundColor: ['#66994D'],
  //               borderColor: 'black',
  //               borderWidth: 2
  //         },
  //         {
  //           label :'Packing',
  //           data: this.Packing_Material_Monthly_Purchase_Data,
  //           backgroundColor: ['#FF6633'],
  //               borderColor: 'black',
  //               borderWidth: 2
  //         },
  //         {
  //           label :'Finished',
  //           data: this.Finished_Material_Monthly_Purchase_Data,
  //           backgroundColor: ['#FF3380' ],
  //               borderColor: 'black',
  //               borderWidth: 2
  //         }
          
  //       ]
  //     },
      
      
  //   });
  // }






}
