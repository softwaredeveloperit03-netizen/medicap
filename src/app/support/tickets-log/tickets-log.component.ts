import { Component, OnInit } from '@angular/core';
import { SupportAccessService } from '../support-access.service';


@Component({
  selector: 'app-tickets-log',
  templateUrl: './tickets-log.component.html',
  styleUrls: ['./tickets-log.component.css']
})
export class TicketsLogComponent implements OnInit {


  lists;

  constructor(public service: SupportAccessService) {}

  ngOnInit(): void {
    this.getAllTicketsForIt();
  }
  
  getAllTicketsForIt(){
    this.service.get('support.php?type=getAllTicketsForIt').subscribe(response=>{
      this.lists = response;
    });
  }

   
  discription;
  isDiscription = false;

  viewDiscription(viewDiscription){
    this.discription = '';
    this.isDiscription = true;
    this.discription = viewDiscription;
  }


  acnkoData = [];
  isAkno = false;

  viewAck(acnkoData){
    this.acnkoData  = [];
    this.isAkno = true;
    this.acnkoData = acnkoData;
  }


  devloperDetails =[];
  isViewAssignDevloper = false;

  viewDeveloperDetails(devloperDetails){
    this.devloperDetails = [];
    this.isViewAssignDevloper = true;
    this.devloperDetails = devloperDetails;
  }


  testerDetails =[];
  isViewAssignTester = false;

  viewTesterDetails(testerDetails){
    this.testerDetails = [];
    this.isViewAssignTester = true;
    this.testerDetails = testerDetails;
  }



  
  viewfile(url) {
     url = this.service.url + 'upload/supportImage/' + url;
    window.open(url, '_blank');
  }

  loading: boolean = false;

 
  searchQuery: string = '';

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.lists;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.lists.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }



  plantColors: { [plantId: string]: string } = {};
  colorPalette = ['#e6f7ff',   '#f9f0ff', '#fffbe6',  '#f6ffed',   '#fff0f6',   '#f0f5ff',   '#e6fffb', '#fcffe6',   '#fff7e6', '#f0fff4',   '#e6f4ff',   '#fef0f0',   '#f0f9ff',   '#fffbf0', '#f3f0ff',  '#fdf6ec',  '#f0ffe0',  '#e8f5e9',  '#fce4ec',   '#f3e5f5'   ];
  private colorIndex = 0;

  getPlantColor(plantId: string): string {
    if (!this.plantColors[plantId]) {
      this.plantColors[plantId] = this.colorPalette[this.colorIndex % this.colorPalette.length];
      this.colorIndex++;
    }
    return this.plantColors[plantId];
  }










}
