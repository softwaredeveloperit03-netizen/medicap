import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DayStoreMasterComponent } from './day-store-master.component';

describe('DayStoreMasterComponent', () => {
  let component: DayStoreMasterComponent;
  let fixture: ComponentFixture<DayStoreMasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DayStoreMasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DayStoreMasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
