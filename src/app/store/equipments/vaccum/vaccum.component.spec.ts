import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VaccumComponent } from './vaccum.component';

describe('VaccumComponent', () => {
  let component: VaccumComponent;
  let fixture: ComponentFixture<VaccumComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ VaccumComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(VaccumComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
