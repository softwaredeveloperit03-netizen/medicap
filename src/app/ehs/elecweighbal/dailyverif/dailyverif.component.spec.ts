import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyverifComponent } from './dailyverif.component';

describe('DailyverifComponent', () => {
  let component: DailyverifComponent;
  let fixture: ComponentFixture<DailyverifComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DailyverifComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyverifComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
