import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css'],
  providers:[DatePipe]
})
export class HistoryComponent implements OnInit {

  isView=false;
  results;
  hide=true;
 
 

  selectedResult = [];
   
  constructor(private service:DataAccessService, private datePipe:DatePipe) { 
 
  }

  ngOnInit() {
    //this.getPreventiveHistory();
    this.getequipment();
  }

  printDiv(): void {
    this.isView = false;
  
    // Clone the document body
    let printContents = document.getElementById('divToPrint').innerHTML;
    let originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
  
    // Print the document
    window.print();
  
    // Restore the original document content
    document.body.innerHTML = originalContents;
  
    // Reload the page after printing
    window.addEventListener('afterprint', () => {
      window.location.reload();
    });
  }
  

  due_type='';
  equipment_id;
  equipments;


  getequipment(){
    this.service.get('engineering/preventive.php?type=getDepartmentsEqupment').subscribe((response: any) => {
      this.equipments = response;
    });
  }

  getPreventiveHistory(){
    this.service.get('engineering/preventive.php?type=getPreventiveHistory&status=Approved&due_type='+this.due_type).subscribe(response=>{
      this.results=response;
    });
  }

  view1(index){
    this.selectedResult=this.filteredMaterials[index];
     this.isView = true;
  }


  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.results.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
           return value && value.toString().toLowerCase().includes(query);
        
      });
    });
  }
 

  download(){
    this.service.open('engineering/preventive.php?type= ')
  }

  downloadView() {
    
  }

}
