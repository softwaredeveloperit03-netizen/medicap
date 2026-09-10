import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AbsulotComponent } from './absulot.component';

describe('AbsulotComponent', () => {
  let component: AbsulotComponent;
  let fixture: ComponentFixture<AbsulotComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AbsulotComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AbsulotComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
