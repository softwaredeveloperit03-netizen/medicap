import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LogsandreportComponent } from './logsandreport.component';

describe('LogsandreportComponent', () => {
  let component: LogsandreportComponent;
  let fixture: ComponentFixture<LogsandreportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LogsandreportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LogsandreportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
