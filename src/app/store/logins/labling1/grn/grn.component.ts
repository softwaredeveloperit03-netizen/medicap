import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-grn',
  templateUrl: './grn.component.html',
  styleUrls: ['./grn.component.css']
})
export class GrnComponent implements OnInit {

  results;
  results1;
  material_name;
  materials;
  selectedResult=[];
  wast;
  wast1=[];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getGRNLog();
    this.getMaterials();
    this.getChkListData();
    
    
  }

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
    
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  getMaterials() {
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    })
  }
 

  getGRNLog() {
    this.service.get('store/label.php?type=getAwaitingGRNRawLabels').subscribe(response => {
      this.results = response;
      this.results1 = response;
    });
  }
  checklist;
  getChkListData() {
    this.service.get('master/checklist.php?type=get_rec_ChkListByTranID&rec_no=').subscribe(response => {
      this.checklist = response;
    });
  }

  printLabel(index){
     this.selectedResult = this.results[index];
    this.service.open('store/raw.php?type=grnLabelsPDF2&id='+this.selectedResult['id'] + '&material_name='+ this.selectedResult['material_name']+'&total_containers='+this.selectedResult['total_containers']+'&batch_no='+this.selectedResult['batch_no']+'&grade='+this.selectedResult['grade']+'&challan_no='+this.selectedResult['challan_no']+'&pack_size='+this.checklist['pack_size']);
  }
  

  
  filterStock(){
    this.results = [];
    for(let i=0; i<this.results1.length; i++){
      let data = this.results1[i];
      if(data.material_name.toUpperCase().includes(this.material_name.toUpperCase()) ){
        this.results.push(data);
      }
    }
  }


  clear(){
    this.material_name = '';
    this.results = this.results1;
  }


}
