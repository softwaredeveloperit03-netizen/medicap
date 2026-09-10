import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FromProductionComponent } from './from-production.component';

describe('FromProductionComponent', () => {
  let component: FromProductionComponent;
  let fixture: ComponentFixture<FromProductionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FromProductionComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FromProductionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
