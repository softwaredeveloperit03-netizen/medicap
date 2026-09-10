import { Component, ChangeDetectionStrategy, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { HttpClient, HttpParams } from '@angular/common/http';
import { map } from 'rxjs/operators';

import {
  startOfDay,
  endOfDay,
  subDays,
  addDays,
  endOfMonth,
  isSameDay,
  isSameMonth,
  addHours,
} from 'date-fns';
import { Subject } from 'rxjs';
import {
  CalendarEvent,
  CalendarEventAction,
  CalendarEventTimesChangedEvent,
  CalendarView,
} from 'angular-calendar';
import { EventColor } from 'calendar-utils';

const colors: Record<string, EventColor> = {
  red: {
    primary: '#ad2121',
    secondary: '#FAE3E3',
  },
  blue: {
    primary: '#1e90ff',
    secondary: '#D1E8FF',
  },
  yellow: {
    primary: '#e3bc08',
    secondary: '#FDF1BA',
  },
};


declare let alertify;
@Component({
  selector: 'app-plan',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './plan.component.html',
  styleUrls: ['./plan.component.css']
})
export class PlanComponent implements OnInit {
  departments;
  isView = false;
  isUpdates = false;
  result;
  results: any = [];
  selectedResult=[];
  equipments;
  equipment = '';
  department = '';
  isEdit = false;
  isChecklist = false;
  view: CalendarView = CalendarView.Month;
  CalendarView = CalendarView;
  viewDate: Date = new Date();
  modalData: {
    action: string;
    event: CalendarEvent;
  };

  actions: CalendarEventAction[] = [
    {
      label: ' <button class="btn btn-sm btn-danger">Send Request</button>',
      a11yLabel: 'Send Rquest',
      onClick: ({ event }: { event: CalendarEvent }): void => {
        this.handleEvent('Edited', event);
      },
    },
    {
      label: ' <button class="btn btn-sm btn-success">Add Checklist</button>',
      a11yLabel: 'Add Checklist',
      onClick: ({ event }: { event: CalendarEvent }): void => { 
        this.handleEvent('Checklist', event);
      },
    },
  ];

  refresh = new Subject<void>();

  events: CalendarEvent[] = [];
    // {
    //   start: subDays(startOfDay(new Date()), 1),
    //   end: addDays(new Date(), 1),
    //   title: 'A 3 day event',
    //   color: { ...colors.red },
    //   actions: this.actions,
    //   allDay: true,
    //   resizable: {
    //     beforeStart: true,
    //     afterEnd: true,
    //   },
    //   draggable: true,
    // },
    // {
    //   start: startOfDay(new Date()),
    //   title: 'An event with no end date',
    //   color: { ...colors.yellow },
    //   actions: this.actions,
    // },
    // {
    //   start: subDays(endOfMonth(new Date()), 3),
    //   end: addDays(endOfMonth(new Date()), 3),
    //   title: 'A long event that spans 2 months',
    //   color: { ...colors.blue },
    //   allDay: true,
    // },
    // {
    //   start: addHours(startOfDay(new Date()), 2),
    //   end: addHours(new Date(), 2),
    //   title: 'A draggable and resizable event',
    //   color: { ...colors.yellow },
    //   actions: this.actions,
    //   resizable: {
    //     beforeStart: true,
    //     afterEnd: true,
    //   },
    //   draggable: true,
    // },
  //];

  activeDayIsOpen: boolean = true;

  constructor(private service: DataAccessService, private http: HttpClient) { }

  ngOnInit() {

    this.service.observableDepartment.subscribe(response => {
      this.departments = response;
    });
    // this.getPreventiveCalender();
    this.getEquipments();
    this.getMaintenanceData();
  }

  getMaintenanceData() {
    var eventsList: any = [];
    this.events = [];
    var month = this.viewDate.getUTCMonth() + 1; //months from 1-12
    var year = this.viewDate.getUTCFullYear();
    this.service.get('master/equipment.php?type=get_monthly_schedule&month=' + month + '&year=' + year + '&rpt_type=preventive').subscribe(response => {
      eventsList = response;
      for (let i = 0; i < eventsList.length; i++) {
        var sd =eventsList[i]['due_date']+'T18:30:00.000Z';
        this.events = [
          ...this.events,
          {
            title: eventsList[i]['equipment_name'],
            start: startOfDay(new Date(sd)),            
            color: colors.red,
            actions: this.actions,
            draggable: false,
            resizable: {
              beforeStart: true,
              afterEnd: true,
            },
          },
        ];
      }
      this.refresh.next();
    });
   
  }
  getPreventiveCalender(data) {
    if (!data.valid) {
      alertify.error("Please Select both department and equipment");
      return;
    }
    this.service.get('engineering/preventive.php?type=getPreventiveSchedule&equipment_name=' + this.equipment + '&department_name=' + this.department).subscribe(response => {
      this.results = response;
    });
  }
  getEquipments() {
    this.service.get('master/equipment.php?type=getPreventEquipments').subscribe(response => {
      this.equipments = response;
     });
  }
  download() {
    this.service.open('engineering/preventive.php?type=downloadPreventiveSchedule&equipment_name=' + this.equipment + '&department_name=' + this.department)
  }

  showEditForm(data) {
    this.result = data;
    this.isUpdates = true;
  }

  editEquipment() {
    let obj = {
      "id": this.result.id,
      "inspection": this.result.inpection_freequency,
      "preventive": this.result.prev_maint_frequency,
    };

    this.service.post('master/equipment.php?type=update_equipment_schedule', JSON.stringify(obj)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.isUpdates = false;
        alertify.success(this.service.t('common.savedSuccess'));
      } else {
        alertify.error('Failed:' + result.status);
      }
    });
  }
  dayClicked({ date, events }: { date: Date; events: CalendarEvent[] }): void {
    if (isSameMonth(date, this.viewDate)) {
      if (
        (isSameDay(this.viewDate, date) && this.activeDayIsOpen === true) ||
        events.length === 0
      ) {
        this.activeDayIsOpen = false;
      } else {
        this.activeDayIsOpen = true;
      }
      this.viewDate = date;
    }
  }

  eventTimesChanged({
    event,
    newStart,
    newEnd,
  }: CalendarEventTimesChangedEvent): void {
    this.getMaintenanceData();
    // this.events = this.events.map((iEvent) => {
    //   if (iEvent === event) {
    //     return {
    //       ...event,
    //       start: newStart,
    //       end: newEnd,
    //     };
    //   }
    //   return iEvent;
    // });
    this.handleEvent('Dropped or resized', event);
  }

  handleEvent(action: string, event: CalendarEvent): void {
    if(action =='Checklist'){
      this.isChecklist = true;
    }
    else if(action =='Edited'){
     // this.isEdit = true;
    }
  }

  addEvent(): void {
    this.events = [
      ...this.events,
      {
        title: 'New event',
        start: startOfDay(new Date()),
        end: endOfDay(new Date()),
        color: colors.red,
        draggable: true,
        resizable: {
          beforeStart: true,
          afterEnd: true,
        },
      },
    ];
  }
 

  setView(view: CalendarView) {
    this.view = view;
  }

  closeOpenMonthViewDay() {
    this.getMaintenanceData();
    this.activeDayIsOpen = false;
  }
}