import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DataretriveComponent } from './dataretrive.component';

describe('DataretriveComponent', () => {
  let component: DataretriveComponent;
  let fixture: ComponentFixture<DataretriveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DataretriveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DataretriveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
