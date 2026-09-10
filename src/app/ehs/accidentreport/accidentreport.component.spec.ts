import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AccidentreportComponent } from './accidentreport.component';

describe('AccidentreportComponent', () => {
  let component: AccidentreportComponent;
  let fixture: ComponentFixture<AccidentreportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AccidentreportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AccidentreportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
