import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DepreviewComponent } from './depreview.component';

describe('DepreviewComponent', () => {
  let component: DepreviewComponent;
  let fixture: ComponentFixture<DepreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DepreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DepreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
