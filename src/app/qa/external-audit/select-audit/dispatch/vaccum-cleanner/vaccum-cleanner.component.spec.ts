import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VaccumCleannerComponent } from './vaccum-cleanner.component';

describe('VaccumCleannerComponent', () => {
  let component: VaccumCleannerComponent;
  let fixture: ComponentFixture<VaccumCleannerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VaccumCleannerComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(VaccumCleannerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
