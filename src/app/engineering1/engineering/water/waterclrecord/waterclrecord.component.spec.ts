import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WaterclrecordComponent } from './waterclrecord.component';

describe('WaterclrecordComponent', () => {
  let component: WaterclrecordComponent;
  let fixture: ComponentFixture<WaterclrecordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WaterclrecordComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WaterclrecordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
