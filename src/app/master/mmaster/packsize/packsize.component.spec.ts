import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PacksizeComponent } from './packsize.component';

describe('PacksizeComponent', () => {
  let component: PacksizeComponent;
  let fixture: ComponentFixture<PacksizeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PacksizeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PacksizeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
