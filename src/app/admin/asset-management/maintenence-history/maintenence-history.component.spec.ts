import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MaintenenceHistoryComponent } from './maintenence-history.component';

describe('MaintenenceHistoryComponent', () => {
  let component: MaintenenceHistoryComponent;
  let fixture: ComponentFixture<MaintenenceHistoryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MaintenenceHistoryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MaintenenceHistoryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
