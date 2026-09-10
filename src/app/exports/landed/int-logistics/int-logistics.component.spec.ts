import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IntLogisticsComponent } from './int-logistics.component';

describe('IntLogisticsComponent', () => {
  let component: IntLogisticsComponent;
  let fixture: ComponentFixture<IntLogisticsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IntLogisticsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(IntLogisticsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
