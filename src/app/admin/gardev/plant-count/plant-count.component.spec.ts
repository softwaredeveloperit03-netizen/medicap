import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PlantCountComponent } from './plant-count.component';

describe('PlantCountComponent', () => {
  let component: PlantCountComponent;
  let fixture: ComponentFixture<PlantCountComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PlantCountComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PlantCountComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
