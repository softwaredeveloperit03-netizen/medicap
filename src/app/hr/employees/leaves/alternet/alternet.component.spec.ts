import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AlternetComponent } from './alternet.component';

describe('AlternetComponent', () => {
  let component: AlternetComponent;
  let fixture: ComponentFixture<AlternetComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AlternetComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AlternetComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
